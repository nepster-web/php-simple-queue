<?php

declare(strict_types=1);

namespace Simple\Queue\Transport;

use Throwable;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Simple\Queue\Status;
use Simple\Queue\Message;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Simple\Queue\MessageHydrator;

/**
 * Class DoctrineDbalTransport
 * @package Simple\Queue\Transport
 */
class DoctrineDbalTransport implements TransportInterface
{
    /** @var Connection */
    private Connection $connection;

    /**
     * DoctrineDbalTransport constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        (new DoctrineDbalTableCreator($this->connection))->createDataBaseTable();
    }

    /**
     * @inheritDoc
     * @throws TransportException
     */
    public function fetchMessage(array $queues = []): ?Message
    {
        $nowTime = time();
        $endAt = microtime(true) + 0.2; // add 200ms

        $select = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(DoctrineDbalTableCreator::getTableName())
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
                throw new TransportException(sprintf('Error reading queue in consumer: "%s".', $e));
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     * @throws TransportException
     */
    public function send(Message $message): void
    {
        $dataMessage = [
            'id' => Uuid::uuid4()->toString(),
            'status' => $message->getStatus(),
            'created_at' => $message->getCreatedAt(),
            'redelivered_at' => $message->getRedeliveredAt(),
            'attempts' => $message->getAttempts(),
            'queue' => $message->getQueue(),
            'event' => $message->getEvent(),
            'is_job' => $message->isJob(),
            'body' => $message->getBody(),
            'priority' => $message->getPriority(),
            'error' => $message->getError(),
            'exact_time' => $message->getExactTime(),
        ];
        try {
            $rowsAffected = $this->connection->insert(DoctrineDbalTableCreator::getTableName(), $dataMessage, [
                'id' => Types::GUID,
                'status' => Types::STRING,
                'created_at' => Types::DATETIME_IMMUTABLE,
                'redelivered_at' => Types::DATETIME_IMMUTABLE,
                'attempts' => Types::SMALLINT,
                'queue' => Types::STRING,
                'event' => Types::STRING,
                'is_job' => Types::BOOLEAN,
                'body' => Types::TEXT,
                'priority' => Types::SMALLINT,
                'error' => Types::TEXT,
                'exact_time' => Types::BIGINT,
            ]);
            if ($rowsAffected !== 1) {
                throw new TransportException('The message was not enqueued. Dbal did not confirm that the record is inserted.');
            }
        } catch (Throwable $e) {
            throw new TransportException('The transport fails to send the message due to some internal error.', 0, $e);
        }
    }

    /**
     * @inheritDoc
     * @throws TransportException
     */
    public function changeMessageStatus(Message $message, Status $status): void
    {
        $this->connection->update(
            DoctrineDbalTableCreator::getTableName(),
            ['status' => (string)$status],
            ['id' => $message->getId()]
        );

        MessageHydrator::changeProperty($message, 'status', $status);
    }

    /**
     * @inheritDoc
     * @throws TransportException
     */
    public function deleteMessage(Message $message): void
    {
        $this->connection->delete(
            DoctrineDbalTableCreator::getTableName(),
            ['id' => $message->getId()],
            ['id' => Types::GUID]
        );
    }
}
