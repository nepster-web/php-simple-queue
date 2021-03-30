<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use DateTimeImmutable;
use Simple\Queue\Config;
use Simple\Queue\Status;
use Simple\Queue\Message;
use Simple\Queue\Consumer;
use Simple\Queue\Producer;
use PHPUnit\Framework\TestCase;
use Simple\Queue\MessageHydrator;
use Simple\QueueTest\Helper\MockConnection;
use Simple\Queue\Transport\DoctrineDbalTransport;

/**
 * Class ConsumerTest
 * @package Simple\QueueTest
 */
class ConsumerTest extends TestCase
{
    public function testAcknowledge(): void
    {
        $id = '71a384ad-952d-417f-9dc5-dfdb5b01704d';
        $message = new Message('my_queue', 'my_data');

        MessageHydrator::changeProperty($message, 'id', $id);

        $store = new class(new MockConnection()) extends DoctrineDbalTransport {
            public static string $deleteMessageId;

            public function deleteMessage(Message $message): void
            {
                self::$deleteMessageId = $message->getId();
            }
        };

        $consumer = new Consumer($store, new Producer($store));
        $consumer->acknowledge($message);

        self::assertEquals($id, $store::$deleteMessageId);
    }

    public function testRejectWithRequeue(): void
    {
        $id = '71a384ad-952d-417f-9dc5-dfdb5b01704d';
        $message = new Message('my_queue', 'my_data');

        MessageHydrator::changeProperty($message, 'id', $id);

        $store = new class(new MockConnection()) extends DoctrineDbalTransport {
            public static string $deleteMessageId;

            public function deleteMessage(Message $message): void
            {
                self::$deleteMessageId = $message->getId();
            }

            public static Message $message;

            public function send(Message $message): void
            {
                self::$message = $message;
            }
        };

        $producer = new Producer($store);

        $consumer = new Consumer($store, $producer);
        $consumer->reject($message, true);

        self::assertEquals($id, $store::$deleteMessageId);

        self::assertEquals(Status::REDELIVERED, $store::$message->getStatus());
        self::assertEquals(
            (new DateTimeImmutable())
                ->modify(sprintf('+%s seconds', Config::getDefault()->getRedeliveryTimeInSeconds()))
                ->format('Y-m-d H:i:s'),
            $store::$message->getRedeliveredAt()->format('Y-m-d H:i:s')
        );
    }

    public function testRejectWithoutRequeue(): void
    {
        $id = '71a384ad-952d-417f-9dc5-dfdb5b01704d';
        $message = new Message('my_queue', 'my_data');

        MessageHydrator::changeProperty($message, 'id', $id);

        $store = new class(new MockConnection()) extends DoctrineDbalTransport {
            public static string $deleteMessageId;

            public function deleteMessage(Message $message): void
            {
                self::$deleteMessageId = $message->getId();
            }
        };

        $consumer = new Consumer($store, new Producer($store));
        $consumer->reject($message);

        self::assertEquals($id, $store::$deleteMessageId);
    }
}
