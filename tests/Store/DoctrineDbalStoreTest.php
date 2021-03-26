<?php

declare(strict_types=1);

namespace Simple\QueueTest\Store;

use Ramsey\Uuid\Uuid;
use Simple\Queue\Status;
use Simple\Queue\Message;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use Simple\Queue\QueueException;
use Simple\Queue\MessageHydrator;
use Simple\Queue\Store\DoctrineDbalStore;
use Simple\QueueTest\Helper\MockConnection;

/**
 * Class DoctrineDbalStoreTest
 * @package Simple\QueueTest\Store
 */
class DoctrineDbalStoreTest extends TestCase
{
    public function testInit(): void
    {
        $connection = new MockConnection();
        $store = new class($connection) extends DoctrineDbalStore {
        };

        $store->init();

        $data = $connection->getSchemaManager()::$data;

        self::assertInstanceOf(Table::class, $data['createTable']);
    }

    // TODO:
    public function testSend(): void
    {
        $connection = new MockConnection();
        $store = new DoctrineDbalStore($connection);

        // $store->send(new Message('my_queue', ''));

       // print_r($connection::$data);

        /*
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
            'is_job' => Types::BOOLEAN,
        ], ($connection::$data)['insert'][2]);*/
    }

    /*public function testFetchMessage(): void
    {

    }*/

    public function testChangeUndefinedMessageStatus(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('The message has no id. It looks like it was not sent to the queue.');

        $store = new DoctrineDbalStore(new MockConnection());

        $store->changeMessageStatus(new Message('my_queue', ''), new Status(Status::IN_PROCESS));
    }

    public function testChangeMessageStatus(): void
    {
        $connection = new MockConnection();

        $store = new DoctrineDbalStore($connection);

        $message = new Message('my_queue', '');
        MessageHydrator::changeProperty($message, 'id', Uuid::uuid4()->toString());

        $store->changeMessageStatus($message, new Status(Status::IN_PROCESS));

        self::assertEquals($message->getId(), $connection::$data['update']['criteria']['id']);
        self::assertEquals(Status::IN_PROCESS, $connection::$data['update']['data']['status']);
    }

    public function testDeleteUndefinedMessage(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('The message has no id. It looks like it was not sent to the queue.');

        $store = new DoctrineDbalStore(new MockConnection());

        $store->deleteMessage(new Message('my_queue', ''));
    }

    public function testDeleteMessage(): void
    {
        $connection = new MockConnection();

        $store = new DoctrineDbalStore($connection);

        $message = new Message('my_queue', '');
        MessageHydrator::changeProperty($message, 'id', Uuid::uuid4()->toString());

        $store->deleteMessage($message);

        self::assertEquals($message->getId(), $connection::$data['delete']['criteria']['id']);
    }
}
