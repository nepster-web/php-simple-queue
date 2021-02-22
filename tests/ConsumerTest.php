<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use LogicException;
use DateTimeImmutable;
use ReflectionProperty;
use Simple\Queue\Status;
use Simple\Queue\Message;
use Simple\Queue\Consumer;
use Simple\Queue\Priority;
use Simple\Queue\Producer;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Simple\QueueTest\Helper\MockConnection;

/**
 * Class ConsumerTest
 * @package Simple\QueueTest
 */
class ConsumerTest extends TestCase
{
    public function testAcknowledge(): void
    {
        $message = new Message('my_queue', 'my_data');

        $this->setMessageId($message, '71a384ad-952d-417f-9dc5-dfdb5b01704d');

        $consumer = new class(new MockConnection(), new Producer(new MockConnection())) extends Consumer {
            public static string $deleteMessageId;

            protected function deleteMessage(string $id): void
            {
                self::$deleteMessageId = $id;
            }
        };

        $consumer->acknowledge($message);

        self::assertEquals('71a384ad-952d-417f-9dc5-dfdb5b01704d', $consumer::$deleteMessageId);
    }

    public function testRejectWithRequeue(): void
    {
        $message = new Message('my_queue', 'my_data');

        $this->setMessageId($message, '71a384ad-952d-417f-9dc5-dfdb5b01704d');

        $producer = new class(new MockConnection()) extends Producer {
            public static Message $message;

            public function send(Message $message): void
            {
                self::$message = $message;
            }
        };

        $consumer = new class(new MockConnection(), $producer) extends Consumer {
            public static string $deleteMessageId;

            protected function deleteMessage(string $id): void
            {
                self::$deleteMessageId = $id;
            }
        };

        $consumer->reject($message, true);

        self::assertEquals('71a384ad-952d-417f-9dc5-dfdb5b01704d', $consumer::$deleteMessageId);

        self::assertEquals(Status::REDELIVERED, $producer::$message->getStatus());
        self::assertEquals(
            (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            $producer::$message->getRedeliveredAt()->format('Y-m-d H:i:s')
        );
    }

    public function testRejectWithoutRequeue(): void
    {
        $message = new Message('my_queue', 'my_data');

        $this->setMessageId($message, '71a384ad-952d-417f-9dc5-dfdb5b01704d');

        $consumer = new class(new MockConnection(), new Producer(new MockConnection())) extends Consumer {
            public static string $deleteMessageId;

            protected function deleteMessage(string $id): void
            {
                self::$deleteMessageId = $id;
            }
        };

        $consumer->reject($message);

        self::assertEquals('71a384ad-952d-417f-9dc5-dfdb5b01704d', $consumer::$deleteMessageId);
    }

    public function testDeleteMessage(): void
    {
        $connection = new class extends MockConnection {
            public function delete($table, array $criteria, array $types = []): int
            {
                self::$data['delete'] = [$table, $criteria, $types];
                return 1;
            }
        };

        $consumer = new class($connection, new Producer($connection)) extends Consumer {
            public function publicDeleteMessage(string $id): void
            {
                $this->deleteMessage($id);
            }
        };

        $consumer->publicDeleteMessage('6a4f09d6-eac6-45f1-80a1-cefde30b43d2');

        self::assertEquals([
            'delete' => [
                'queue',
                [
                    'id' => '6a4f09d6-eac6-45f1-80a1-cefde30b43d2',
                ],
                [
                    'id' => Types::GUID
                ]
            ],
        ], $connection::$data);
    }

    public function testRedeliveredMessage(): void
    {
        $message = new Message('my_queue', 'my_data');

        $consumer = new class(new MockConnection(), new Producer(new MockConnection())) extends Consumer {
            public function publicForRedeliveryMessage(Message $message): Message
            {
                return $this->forRedeliveryMessage($message);
            }
        };

        $newMessage = $consumer->publicForRedeliveryMessage($message);

        self::assertEquals(Status::NEW, $message->getStatus());
        self::assertNull($message->getRedeliveredAt());

        self::assertEquals(Status::REDELIVERED, $newMessage->getStatus());
        self::assertEquals(
            (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            $newMessage->getRedeliveredAt()->format('Y-m-d H:i:s')
        );
    }

    public function testCreateMessageFromArrayData(): void
    {
        $consumer = new class(new MockConnection(), new Producer(new MockConnection())) extends Consumer {
            public function publicCreateMessage(array $data): Message
            {
                return $this->createMessage($data);
            }
        };

        $message = $consumer->publicCreateMessage([
            'id' => '3114be4a-33ae-499a-9e02-db3eeb077dfa',
            'body' => 'my_data',
            'queue' => 'my_queue',
            'event' => 'my_event',
            'attempts' => 7,
            'error' => 'Exception',
            'status' => Status::NEW,
            'priority' => Priority::DEFAULT,
            'exact_time' => strtotime('2021-02-22 10:00:00'),
            'created_at' => '2021-02-22 10:00:00',
            'redelivered_at' => '2021-02-22 11:00:00',
        ]);

        self::assertEquals('3114be4a-33ae-499a-9e02-db3eeb077dfa', $message->getId());
        self::assertEquals('my_queue', $message->getQueue());
        self::assertEquals('my_event', $message->getEvent());
        self::assertEquals(7, $message->getAttempts());
        self::assertEquals('my_data', $message->getBody());
        self::assertEquals('Exception', $message->getError());
        self::assertEquals(Status::NEW, $message->getStatus());
        self::assertEquals(Priority::DEFAULT, $message->getPriority());
        self::assertEquals(strtotime('2021-02-22 10:00:00'), $message->getExactTime());
        self::assertEquals('2021-02-22 10:00:00', $message->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertEquals('2021-02-22 11:00:00', $message->getRedeliveredAt()->format('Y-m-d H:i:s'));
    }

    public function testCreateMessageFromEmptyArray(): void
    {
        $consumer = new class(new MockConnection(), new Producer(new MockConnection())) extends Consumer {
            public function publicCreateMessage(array $data): Message
            {
                return $this->createMessage($data);
            }
        };

        $message = $consumer->publicCreateMessage([]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The message has no id. It looks like it was not sent to the queue.');
        $message->getId();

        self::assertEquals('default', $message->getQueue());
        self::assertEquals('', $message->getBody());
        self::assertNull($message->getEvent());
        self::assertNull($message->getError());
        self::assertNull($message->getRedeliveredAt());
        self::assertEquals(0, $message->getAttempts());
        self::assertEquals(Status::NEW, $message->getStatus());
        self::assertEquals(Priority::DEFAULT, $message->getPriority());
        self::assertEquals(time(), $message->getExactTime());
        self::assertEquals(
            (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            $message->getCreatedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * @param Message $message
     * @param string $id
     * @throws \ReflectionException
     */
    private function setMessageId(Message $message, string $id): void
    {
        $reflection = new ReflectionProperty(get_class($message), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($message, $id);
    }
}
