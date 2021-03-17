<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use DateTimeImmutable;
use ReflectionProperty;
use Simple\Queue\Config;
use Simple\Queue\Status;
use Simple\Queue\Message;
use Simple\Queue\Consumer;
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

        $config = new Config();
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
            (new DateTimeImmutable())
                ->modify(sprintf('+%s seconds', $config->getRedeliveryTimeInSeconds()))
                ->format('Y-m-d H:i:s'),
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

        $config = new Config();
        $consumer = new class(new MockConnection(), new Producer(new MockConnection()), $config) extends Consumer {
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
            (new DateTimeImmutable())
                ->modify(sprintf('+%s seconds', $config->getRedeliveryTimeInSeconds()))
                ->format('Y-m-d H:i:s'),
            $newMessage->getRedeliveredAt()->format('Y-m-d H:i:s')
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
