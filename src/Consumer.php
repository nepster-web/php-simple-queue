<?php

declare(strict_types=1);

namespace Simple\Queue;

use Exception;
use Throwable;
use LogicException;
use RuntimeException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use Doctrine\DBAL\Types\Types;
use Laminas\Hydrator\ReflectionHydrator;
use Doctrine\DBAL\Schema\SchemaException;
use Laminas\Hydrator\Strategy\HydratorStrategy;

/**
 * Class Consumer
 * @package Simple\Queue
 */
class Consumer
{
    /** Use to mark results as successful */
    public const ACK = 'ACK';

    /** Use one more try if necessary */
    public const REJECT = 'REJECT';

    /** Use in case of a delayed queue */
    public  const REQUEUE = 'REQUEUE';

    /** @var Connection */
    protected Connection $connection;

    /** @var Producer */
    protected Producer $producer;

    /** @var array */
    protected array $processors = [];

    /** @var Config|null */
    private ?Config $config;

    /**
     * Consumer constructor.
     * @param Connection $connection
     * @param Producer $producer
     * @param Config|null $config
     */
    public function __construct(Connection $connection, Producer $producer, ?Config $config = null)
    {
        $this->connection = $connection;
        $this->producer = $producer;
        $this->config = $config ?? new Config();
    }

    /**
     * Fetch the next message from the queue
     *
     * @param array $queues
     * @return Message|null
     */
    public function fetchMessage(array $queues = []): ?Message
    {
        $nowTime = time();
        $endAt = microtime(true) + 0.2; // add 200ms

        $select = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(QueueTableCreator::getTableName())
            ->andWhere('status IN (:statuses)')
            ->andWhere('redelivered_at IS NULL OR redelivered_at <= :redeliveredAt')
            ->andWhere('exact_time <= :nowTime')
            ->addOrderBy('priority', 'asc')
            ->addOrderBy('created_at', 'asc')
            ->setParameter('redeliveredAt', new DateTimeImmutable('now'), Types::DATETIME_IMMUTABLE)
            ->setParameter('statuses', [Status::NEW, Status::REDELIVERED], Connection::PARAM_STR_ARRAY)
            ->setParameter('nowTime', $nowTime, Types::INTEGER)
            ->setMaxResults(1);

        if (count($queues)) {
            $select
                ->where('queue IN (:queues)')
                ->setParameter('queues', $queues, Connection::PARAM_STR_ARRAY);
        }

        while (microtime(true) < $endAt) {
            try {
                $deliveredMessage = $select->execute()->fetchAssociative();

                if (empty($deliveredMessage)) {
                    continue;
                }

                return $this->createMessage($deliveredMessage);
            } catch (Throwable $e) {
                throw new RuntimeException(sprintf('Error reading queue in consumer: "%s".', $e));
            }
        }

        return null;
    }

    /**
     * Delete message from queue
     *
     * @param string $id
     * @throws \Doctrine\DBAL\Exception
     */
    protected function deleteMessage(string $id): void
    {
        if (empty($id)) {
            throw new LogicException(sprintf('Expected record was removed but it is not. Delivery id: "%s"', $id));
        }

        $this->connection->delete(
            QueueTableCreator::getTableName(),
            ['id' => $id],
            ['id' => Types::GUID]
        );
    }

    /**
     * Redelivered a message to the queue
     *
     * @param Message $message
     * @return Message
     */
    protected function forRedeliveryMessage(Message $message): Message
    {
        // TODO: add the ability to specify the period time.

        $redeliveredMessage = (new Message($message->getQueue(), $message->getBody()))
            ->changePriority(new Priority($message->getPriority()))
            ->setEvent($message->getEvent())
            ->setRedeliveredAt(
                $message->getRedeliveredAt() ?:
                    (new DateTimeImmutable('now'))
                        ->modify(sprintf('+%s seconds', $this->config->redeliveryTimeInSeconds))
            );

        return (new MessageHydrator($redeliveredMessage))
            ->changeStatus($message->getStatus() === Status::UNDEFINED_HANDLER ?: Status::REDELIVERED)
            ->getMessage();
    }

    /**
     * Create entity Message from array data
     *
     * @param array $data
     * @return Message
     * @throws Exception
     */
    protected function createMessage(array $data): Message
    {
        $strategy = new HydratorStrategy(new ReflectionHydrator(), Message::class);

        /** @var Message $message */
        $message = $strategy->hydrate(array_merge($data, [
            "queue" => $data['queue'] ?? 'default',
            "event" => $data['event'] ?? null,
            "body" => $data['body'] ?? '',
            "error" => $data['error'] ?? null,
            "attempts" => $data['attempts'] ?? 0,
            "status" => new Status($data['status'] ?? Status::NEW),
            "priority" => new Priority((int)($data['priority'] ?? Priority::DEFAULT)),
            "exactTime" => $data['exact_time'] ?? time(),
            "createdAt" => new DateTimeImmutable($data['created_at'] ?? 'now'),
            "redeliveredAt" => isset($data['redelivered_at']) ? new DateTimeImmutable($data['redelivered_at']) : null,
        ]));

        return $message;
    }

    /**
     * The message has been successfully processed and will be removed from the queue
     *
     * @param Message $message
     * @throws \Doctrine\DBAL\Exception
     */
    public function acknowledge(Message $message): void
    {
        $this->deleteMessage($message->getId());
    }

    /**
     * Reject message with requeue option
     *
     * @param Message $message
     * @param bool $requeue
     * @throws \Doctrine\DBAL\Exception
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        $this->acknowledge($message);

        if ($requeue) {
            $this->producer->send($this->forRedeliveryMessage($message));
        }
    }

    /**
     * Registering a queue handler
     *
     * @param string $queue
     * @param callable $processor
     */
    public function bind(string $queue, callable $processor): void
    {
        if (preg_match('/^[0-1a-zA-Z-._]$/mu', $queue) === false) {
            throw new InvalidArgumentException(sprintf('The queue "%s" contains invalid characters.', $queue));
        }

        if (isset($this->processors[$queue])) {
            throw new RuntimeException(sprintf('Queue "%s" is already registered in the processors.', $queue));
        }

        $this->processors[$queue] = $processor;
    }

    /**
     * @param array $queues
     * @throws SchemaException
     * @throws \Doctrine\DBAL\Exception
     */
    public function consume(array $queues = []): void
    {
        (new QueueTableCreator($this->connection))->createDataBaseTable();

        while (true) {
            if ($message = $this->fetchMessage($queues)) {
                try {
                    $this->processing($message);
                } catch (Throwable $throwable) {
                    $message->setRedeliveredAt(
                        (new DateTimeImmutable('now'))->modify('+5 minutes')
                    );
                    $message = (new MessageHydrator($message))
                        ->changeError($throwable->getMessage())
                        ->getMessage();

                    if ($message->getAttempts() >= $this->config->numberOfAttemptsBeforeFailure) {
                        $message = (new MessageHydrator($message))
                            ->changeStatus(Status::FAILURE)
                            ->changeError($throwable->getMessage())
                            ->getMessage();
                    }

                    try {
                        $this->reject($message, true);
                    } catch (RuntimeException|\Doctrine\DBAL\Exception $exception) {
                        // maybe lucky later
                    }
                }
            }
        }
    }

    /**
     * @param Message $message
     * @throws \Doctrine\DBAL\Exception
     */
    private function processing(Message $message): void
    {
        if (isset($this->processors[$message->getQueue()])) {
            $result = $this->processors[$message->getQueue()]($message, $this->producer);
            $this->checkStatus($result, $message);

            return;
        }

        if (is_a($message->getEvent(), Job::class)) {

            /** @var Job $job */
            $job = new $message->getEvent(); // TODO: The job can inject custom dependencies
            $result = $job->handle($message, $this->producer);
            $this->checkStatus($result, $message);

            return;
        }

        $redeliveredMessage = (new MessageHydrator($message))
            ->changeStatus(Status::UNDEFINED_HANDLER)
            ->getMessage();

        $this->reject($redeliveredMessage, true);
    }

    /**
     * @param string $result
     * @param Message $message
     * @throws \Doctrine\DBAL\Exception
     */
    private function checkStatus(string $result, Message $message): void
    {
        if ($result === self::ACK) {
            $this->acknowledge($message);

            return;
        }

        if ($result === self::REJECT) {
            $this->reject($message);

            return;
        }

        if ($result === self::REQUEUE) {
            $message->setRedeliveredAt(
                (new DateTimeImmutable('now'))
                    ->modify(sprintf('+%s seconds', $this->config->redeliveryTimeInSeconds))
            );
            $this->reject($message, true);

            return;
        }

        throw new InvalidArgumentException(sprintf('Unsupported result status: "%s".', $result));
    }
}
