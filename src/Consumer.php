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

/**
 * Class Consumer
 *
 * TODO: 1 - create job instance
 * TODO: 2 - move db operations to other class
 * TODO: 3 - dispatch job
 * TODO: 4 - deserialize data to job and processor
 *
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
     * Change message status from queue
     *
     * @param Message $message
     * @param Status $status
     */
    protected function changeMessageStatus(Message $message, Status $status): void
    {
        if (empty($message->getId())) {
            throw new LogicException('The expected message has no identifier for changing status in queue.');
        }

        $this->connection->update(
            QueueTableCreator::getTableName(),
            ['status' => (string)$status],
            ['id' => $message->getId()]
        );

        MessageHydrator::changeProperty($message, 'status', $status);
    }

    /**
     * Delete message from queue
     *
     * @param Message $message
     */
    protected function deleteMessage(Message $message): void
    {
        if (empty($message->getId())) {
            throw new LogicException('The expected message has no identifier for removing from queue.');
        }

        $this->connection->delete(
            QueueTableCreator::getTableName(),
            ['id' => $message->getId()],
            ['id' => Types::GUID]
        );
    }

    /**
     * The message has been successfully processed and will be removed from the queue
     *
     * @param Message $message
     */
    public function acknowledge(Message $message): void
    {
        $this->deleteMessage($message);
    }

    /**
     * Reject message with requeue option
     *
     * @param Message $message
     * @param bool $requeue
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        $this->acknowledge($message);

        if ($requeue) {
            $this->producer->send($this->makeRedeliveryMessage($message));
        }
    }

    /**
     * Redelivered a message to the queue
     *
     * TODO: add test for is job
     * TODO: add test for set error
     * TODO: add test for change status
     * TODO: add test for correct increase attempt
     * TODO: add test for count job attempts
     *
     * @param Message $message
     * @return Message
     */
    protected function makeRedeliveryMessage(Message $message): Message
    {
        $newStatus = ($message->getStatus() === Status::NEW || $message->getStatus() === Status::IN_PROCESS) ?
            Status::REDELIVERED :
            $message->getStatus();

        if ($message->isJob()) {
            $job = $this->getJobInstance($message);

            if (($message->getAttempts() + 1) >= ($job->attempts() ?: $this->config->getNumberOfAttemptsBeforeFailure())) {
                $newStatus = Status::FAILURE;
            }
        }

        $redeliveredTime = (new DateTimeImmutable('now'))
            ->modify(sprintf('+%s seconds', $this->config->getRedeliveryTimeInSeconds()));

        if (
            $message->getRedeliveredAt() &&
            $message->getRedeliveredAt()->getTimestamp() > $redeliveredTime->getTimestamp()
        ) {
            $redeliveredTime = $message->getRedeliveredAt();
        }

        $redeliveredMessage = (new Message($message->getQueue(), $message->getBody()))
            ->changePriority($message->getPriority())
            ->setEvent($message->getEvent())
            ->setRedeliveredAt($redeliveredTime);

        return (new MessageHydrator($redeliveredMessage))
            ->changeStatus($newStatus)
            ->jobable($message->isJob())
            ->setError($message->getError())
            ->changeAttempts($message->getAttempts() + 1)
            ->getMessage();
    }


    /**
     * @param Message $message
     */
    protected function processing(Message $message): void
    {
        $this->changeMessageStatus($message, new Status(Status::IN_PROCESS));

        if ($message->isJob()) {
            try {
                $job = $this->getJobInstance($message);

                $result = $job->handle($message, $this->producer);

                $this->processResult($result, $message);
            } catch (Exception $exception) {
                MessageHydrator::changeProperty($message, 'status', new Status(Status::REDELIVERED));
                MessageHydrator::changeProperty($message, 'error', (string)$exception);

                $this->reject($message, true);
            }
            return;
        }

        if (isset($this->processors[$message->getQueue()])) {
            $result = $this->processors[$message->getQueue()]($message, $this->producer);

            $this->processResult($result, $message);

            return;
        }

        MessageHydrator::changeProperty($message, 'status', new Status(Status::UNDEFINED_HANDLER));
        MessageHydrator::changeProperty($message, 'error', 'Could not find any job or processor.');

        $this->reject($message, true);
    }

    /**
     * Get job instance
     *
     * @param Message $message
     * @return Job
     */
    protected function getJobInstance(Message $message): Job
    {
        $jobClass = $message->getEvent();

        if ($this->config->hasJob((string)$message->getEvent())) {
            $jobClass = $this->config->getJob((string)$message->getEvent());
        } elseif ($message->getEvent() && class_exists($message->getEvent())) {
            $jobClass = $message->getEvent();
        } else {
            throw new RuntimeException(sprintf('Job "%s" is not an executable class.', $jobClass));
        }

        if (is_subclass_of($jobClass, Job::class) === false) {
            throw new RuntimeException(sprintf('Job "%s" should extend "%s".', $jobClass, Job::class));
        }

        // TODO: create instance

        /** @var Job $job */
        $job = new $jobClass;

        return $job;
    }

    /**
     * @param string $result
     * @param Message $message
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
                    try {
                        MessageHydrator::changeProperty($message, 'error', (string)$throwable);
                        $this->reject($message, true);
                    } catch (\Doctrine\DBAL\Exception $exception) {
                        // maybe lucky later
                    }
                }
                continue;
            }
            usleep(200000); // 0.2 second
        }
    }
}
