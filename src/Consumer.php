<?php

declare(strict_types=1);

namespace Simple\Queue;

use Throwable;
use LogicException;
use RuntimeException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\SchemaException;

/**
 * Class Consumer
 * @package Simple\Queue
 */
class Consumer
{
    /** Use to mark results as successful */
    public const STATUS_ACK = 'ACK';

    /** Use one more try if necessary */
    public const STATUS_REJECT = 'REJECT';

    /** Use in case of a delayed queue */
    public const STATUS_REQUEUE = 'REQUEUE';

    /** @var Connection */
    protected Connection $connection;

    /** @var Producer */
    protected Producer $producer;

    /** @var Config|null */
    private ?Config $config;

    /** @var array */
    protected array $processors = [];

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
        $this->config = $config ?: Config::getDefault();
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

                return MessageHydrator::createMessage($deliveredMessage);
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
            throw new LogicException(sprintf('Expected record was removed but it is not. Delivery id: "%s".', $id));
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
        $redeliveredTime = (new DateTimeImmutable('now'))
            ->modify(sprintf('+%s seconds', $this->config->getRedeliveryTimeInSeconds()));

        $redeliveredMessage = (new Message($message->getQueue(), $message->getBody()))
            ->changePriority($message->getPriority())
            ->setEvent($message->getEvent())
            ->setRedeliveredAt($message->getRedeliveredAt() ?: $redeliveredTime);

        return (new MessageHydrator($redeliveredMessage))
            ->changeStatus(
                ($message->getStatus() === Status::UNDEFINED_HANDLER) ?
                    Status::UNDEFINED_HANDLER :
                    Status::REDELIVERED
            )
            ->getMessage();
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
     * Registering a processor for queue
     *
     * @param string $queue
     * @param callable $processor
     */
    public function bind(string $queue, callable $processor): void
    {
        if (preg_match('/^[0-9a-zA-Z-._]$/mu', $queue) === false) {
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

        // TODO: check which queues are binding

        while (true) {
            if ($message = $this->fetchMessage($queues)) {
                try {
                    $this->processing($message);
                } catch (Throwable $throwable) {
                    if ($message->getAttempts() >= $this->config->getNumberOfAttemptsBeforeFailure()) {
                        $message = (new MessageHydrator($message))
                            ->changeStatus(Status::FAILURE)
                            ->setError((string)$throwable)
                            ->getMessage();
                    } else {
                        $message = (new MessageHydrator($message))
                            ->setError((string)$throwable)
                            ->increaseAttempt()
                            ->getMessage();
                    }
                    try {
                        $this->reject($message, true);
                    } catch (\Doctrine\DBAL\Exception $exception) {
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
    protected function processing(Message $message): void
    {
        if ($message->isJob()) {
            if ($this->config->hasJob((string)$message->getEvent())) {
                $jobClass = $this->config->getJob((string)$message->getEvent());
            } elseif ($message->getEvent() && class_exists($message->getEvent())) {
                $jobClass = $message->getEvent();
            } else {
                $this->rejectByError(
                    $message,
                    sprintf('Could not find job: "%s".', $message->getEvent()),
                    Status::FAILURE
                );

                return;
            }

            if (is_a($jobClass, Job::class)) {
                /** @var Job $job */
                $job = new $job; // TODO: The job can inject custom dependencies
                $result = $job->handle($message, $this->producer);

                $this->processResult($result, $message);

                return;
            }

            $this->rejectByError(
                $message,
                sprintf('The job "%s" does not match the required parameters.', $message->getEvent()),
                Status::FAILURE
            );

            return;
        }

        if (isset($this->processors[$message->getQueue()])) {
            $result = $this->processors[$message->getQueue()]($message, $this->producer);

            $this->processResult($result, $message);

            return;
        }

        $this->rejectByError($message, sprintf('Could not find any job or processor.'), Status::UNDEFINED_HANDLER);
    }

    /**
     * @param Message $message
     * @param string $error
     * @param string $status
     * @throws \Doctrine\DBAL\Exception
     */
    protected function rejectByError(Message $message, string $error, string $status): void
    {
        $redeliveredMessage = (new MessageHydrator($message))
            ->changeStatus($status)
            ->setError($error)
            ->getMessage();

        $this->reject($redeliveredMessage, true);
    }

    /**
     * @param string $result
     * @param Message $message
     * @throws \Doctrine\DBAL\Exception
     */
    protected function processResult(string $result, Message $message): void
    {
        if ($result === self::STATUS_ACK) {
            $this->acknowledge($message);

            return;
        }

        if ($result === self::STATUS_REJECT) {
            $this->reject($message);

            return;
        }

        if ($result === self::STATUS_REQUEUE) {
            $this->reject($message, true);

            return;
        }

        throw new InvalidArgumentException(sprintf('Unsupported result status: "%s".', $result));
    }
}
