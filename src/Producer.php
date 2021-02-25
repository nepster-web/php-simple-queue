<?php

declare(strict_types=1);

namespace Simple\Queue;

use Throwable;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;

/**
 * Class Producer
 * @package Simple\Queue
 */
class Producer
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * Producer constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $queue
     * @param $body
     * @return Message
     */
    public function createMessage(string $queue, $body): Message
    {
        if (is_callable($body)) {
            throw new InvalidArgumentException('The closure cannot be serialized.');
        }

        if (is_object($body) && method_exists($body, '__toString')) {
            $body = (string)$body;
        }

        if (is_object($body) || is_array(is_object($body))) {
            // TODO: need to serialize $body
        }

        return new Message($queue, (string)$body);
    }

    /**
     * @param string $jobName
     * @param array $data
     */
    public function dispatch(string $jobName, array $data): void
    {
        // TODO: need to serialize $data

        // TODO: check jobs

        // TODO: send message with job info
    }

    /**
     * @param Message $message
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
            'body' => $message->getBody(),
            'priority' => $message->getPriority(),
            'error' => $message->getError(),
            'exact_time' => $message->getExactTime(),
        ];

        try {

            $rowsAffected = $this->connection->insert(QueueTableCreator::getTableName(), $dataMessage, [
                'id' => Types::GUID,
                'status' => Types::STRING,
                'created_at' => Types::DATETIME_IMMUTABLE,
                'redelivered_at' => Types::DATETIME_IMMUTABLE,
                'attempts' => Types::SMALLINT,
                'queue' => Types::STRING,
                'event' => Types::STRING,
                'body' => Types::TEXT,
                'priority' => Types::SMALLINT,
                'error' => Types::TEXT,
                'exact_time' => Types::BIGINT,
            ]);

            if ($rowsAffected !== 1) {
                throw new RuntimeException('The message was not enqueued. Dbal did not confirm that the record is inserted.');
            }

        } catch (Throwable $e) {
            throw new RuntimeException('The transport fails to send the message due to some internal error.', 0, $e);
        }
    }
}
