<?php

declare(strict_types=1);

namespace Simple\QueueTest\Transport;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Simple\Queue\Status;
use Simple\Queue\Message;
use Simple\Queue\Priority;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use Simple\Queue\QueueException;
use Simple\Queue\MessageHydrator;
use Simple\QueueTest\Helper\MockConnection;
use Simple\Queue\Transport\TransportException;
use Simple\Queue\Transport\DoctrineDbalTransport;

/**
 * Class DoctrineDbalTransportTest
 * @package Simple\QueueTest\Store
 */
class DoctrineDbalTransportTest extends TestCase
{
    public function testInit(): void
    {
        $connection = new MockConnection();
        $transport = new class($connection) extends DoctrineDbalTransport {
        };

        $transport->init();

        $data = $connection->getSchemaManager()::$data;

        self::assertInstanceOf(Table::class, $data['createTable']);
    }

    public function testSend(): void
    {
        $connection = new MockConnection(null, [
            'insert' => 1,
        ]);
        $transport = new DoctrineDbalTransport($connection);

        $transport->send(new Message('my_queue', ''));

        self::assertNotNull($connection::$data['insert']['data']['id']);
        self::assertNull($connection::$data['insert']['data']['event']);
        self::assertNull($connection::$data['insert']['data']['error']);
        self::assertNull($connection::$data['insert']['data']['redelivered_at']);
        self::assertEquals(0, $connection::$data['insert']['data']['attempts']);
        self::assertEquals('my_queue', $connection::$data['insert']['data']['queue']);
        self::assertEquals('', $connection::$data['insert']['data']['body']);
        self::assertEquals(Status::NEW, $connection::$data['insert']['data']['status']);
        self::assertEquals(Priority::DEFAULT, $connection::$data['insert']['data']['priority']);
        self::assertEquals(date('Y-m-d H:i:s'), $connection::$data['insert']['data']['created_at']);
    }

    public function testFetchMessageWithQueueList(): void
    {
        $connection = new MockConnection(null, [
            'fetchAssociative' => [
                'queue' => 'default',
                'event' => null,
                'is_job' => false,
                'body' => '',
                'error' => null,
                'attempts' => 0,
                'status' => Status::NEW,
                'priority' => Priority::DEFAULT,
                'exact_time' => time(),
                'created_at' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
                'redelivered_at' => null,
            ],
        ]);

        $transport = new DoctrineDbalTransport($connection);

        $message = $transport->fetchMessage(['my_queue']);

        self::assertEquals('default', $message->getQueue());
        self::assertEquals(false, $message->isJob());
        self::assertEquals(null, $message->getError());
        self::assertEquals(0, $message->getAttempts());
        self::assertEquals(Status::NEW, $message->getStatus());
        self::assertEquals(Priority::DEFAULT, $message->getPriority());
    }

    public function testFetchMessageWithException(): void
    {
        $expectExceptionMessage = null;

        try {
            MessageHydrator::createMessage([
                'bad data'
            ]);
        } catch (\Throwable $throwable) {
            $expectExceptionMessage = $throwable->getMessage();
        }

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage(sprintf('Error reading queue in consumer: "%s".', $expectExceptionMessage));

        $connection = new MockConnection(null, [
            'fetchAssociative' => [
                'bad data'
            ],
        ]);

        $transport = new DoctrineDbalTransport($connection);

        $message = $transport->fetchMessage(['my_queue']);

        self::assertNull($message);
    }

    public function testFetchMessageEmpty(): void
    {
        $connection = new MockConnection(null, [
            'fetchAssociative' => [],
        ]);

        $transport = new DoctrineDbalTransport($connection);

        $message = $transport->fetchMessage(['my_queue']);

        self::assertNull($message);
    }

    public function testChangeUndefinedMessageStatus(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('The message has no id. It looks like it was not sent to the queue.');

        $transport = new DoctrineDbalTransport(new MockConnection());

        $transport->changeMessageStatus(new Message('my_queue', ''), new Status(Status::IN_PROCESS));
    }

    public function testChangeMessageStatus(): void
    {
        $connection = new MockConnection();

        $transport = new DoctrineDbalTransport($connection);

        $message = new Message('my_queue', '');
        MessageHydrator::changeProperty($message, 'id', Uuid::uuid4()->toString());

        $transport->changeMessageStatus($message, new Status(Status::IN_PROCESS));

        self::assertEquals($message->getId(), $connection::$data['update']['criteria']['id']);
        self::assertEquals(Status::IN_PROCESS, $connection::$data['update']['data']['status']);
    }

    public function testDeleteUndefinedMessage(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('The message has no id. It looks like it was not sent to the queue.');

        $transport = new DoctrineDbalTransport(new MockConnection());

        $transport->deleteMessage(new Message('my_queue', ''));
    }

    public function testDeleteMessage(): void
    {
        $connection = new MockConnection();

        $transport = new DoctrineDbalTransport($connection);

        $message = new Message('my_queue', '');
        MessageHydrator::changeProperty($message, 'id', Uuid::uuid4()->toString());

        $transport->deleteMessage($message);

        self::assertEquals($message->getId(), $connection::$data['delete']['criteria']['id']);
    }
}
