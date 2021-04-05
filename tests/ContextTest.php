<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use Simple\Queue\Context;
use Simple\Queue\Message;
use Simple\Queue\Producer;
use PHPUnit\Framework\TestCase;
use Simple\QueueTest\Helper\MockConnection;
use Simple\Queue\Transport\DoctrineDbalTransport;

/**
 * Class ContextTest
 * @package Simple\QueueTest
 */
class ContextTest extends TestCase
{
    public function testDefault(): void
    {
        $connection = new MockConnection();

        $transport = new class($connection) extends DoctrineDbalTransport {
            public static Message $message;

            public function send(Message $message): void
            {
                self::$message = $message;
            }
        };

        $producer = new Producer($transport);
        $message = $producer->createMessage('my_queue', '');

        $context = new Context($producer, $message, []);

        self::assertEquals($producer, $context->getProducer());
        self::assertEquals($message, $context->getMessage());
        self::assertEquals([], $context->getData());
    }
}
