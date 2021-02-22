<?php

declare(strict_types=1);

namespace Simple\Queue;

use Exception;
use Throwable;
use LogicException;
use RuntimeException;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\Hydrator\Strategy\HydratorStrategy;

/**
 * Class Consumer
 * @package Simple\Queue
 */
class Consumer
{
    /** @var Connection */
    protected Connection $connection;

    /** @var Producer */
    protected Producer $producer;

    /**
     * Consumer constructor.
     * @param Connection $connection
     * @param Producer $producer
     */
    public function __construct(Connection $connection, Producer $producer)
    {
        $this->connection = $connection;
        $this->producer = $producer;
    }

    /**
     * Fetch the next message from the queue
     *
     * @param string $queue
     * @return Message|null
     */
    public function fetchMessage(string $queue): ?Message
    {
        $nowTime = time();
        $endAt = microtime(true) + 0.2; // add 200ms

        $select = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(QueueTableCreator::getTableName())
            ->where('queue = :queue')
            ->andWhere('status IN (:statuses)')
            ->andWhere('redelivered_at IS NULL OR redelivered_at <= :redeliveredAt')
            ->andWhere('exact_time <= :nowTime')
            ->addOrderBy('priority', 'asc')
            ->addOrderBy('created_at', 'asc')
            ->setParameter('queue', $queue)
            ->setParameter(
                'redeliveredAt',
                new DateTimeImmutable('now'),
                Types::DATETIME_IMMUTABLE
            )
            ->setParameter(
                'statuses',
                [Status::NEW, Status::REDELIVERED],
                Connection::PARAM_STR_ARRAY
            )
            ->setParameter('nowTime', $nowTime, Types::INTEGER)
            ->setMaxResults(1);

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
     * Remove message from queue
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
        // TODO: add the ability to specify the period time

        $redeliveredMessage = (new Message($message->getQueue(), $message->getBody()))
            ->changePriority(new Priority($message->getPriority()))
            ->setEvent($message->getEvent())
            ->setRedeliveredAt($message->getRedeliveredAt() ?: new DateTimeImmutable());

        $hydrator = new ReflectionHydrator();

        /** @var Message $redeliveredMessage */
        $redeliveredMessage = $hydrator->hydrate([
            'status' => new Status(Status::REDELIVERED),
        ], $redeliveredMessage);

        return $redeliveredMessage;
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
}
