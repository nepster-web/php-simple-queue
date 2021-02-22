<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use Ramsey\Uuid\Uuid;
use RuntimeException;
use Simple\Queue\Message;
use Simple\Queue\Producer;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Simple\QueueTest\Helper\MockConnection;

/**
 * Class ProducerTest
 * @package Simple\QueueTest
 */
class ProducerTest extends TestCase
{
    public function testSuccessSendMessage(): void
    {
        $connection = new class extends MockConnection {
            public function insert($table, array $data, array $types = []): int
            {
                self::$data['insert'] = [$table, $data, $types];
                return 1;
            }
        };

        $producer = new Producer($connection);
        $producer->send(new Message('my_queue', ''));

        self::assertEquals('queue', ($connection::$data)['insert'][0]);
        self::assertTrue(Uuid::isValid(($connection::$data)['insert'][1]['id']));
        self::assertEquals([
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
        ], ($connection::$data)['insert'][2]);
    }

    public function testFailureSendMessage(): void
    {
        $connection = new class extends MockConnection {
            public function insert($table, array $data, array $types = []): int
            {
                return 0;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The transport fails to send the message due to some internal error.');

        $producer = new Producer($connection);
        $producer->send(new Message('my_queue', ''));
    }
}