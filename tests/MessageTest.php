<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use LogicException;
use DateTimeImmutable;
use Simple\Queue\Status;
use Simple\Queue\Message;
use Simple\Queue\Priority;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageTest
 * @package Simple\QueueTest
 */
class MessageTest extends TestCase
{
    public function testCreateNewDefaultMessage(): void
    {
        $body =  json_encode([], JSON_THROW_ON_ERROR);

        $time = time();
        $message = new Message('my_queue', $body);


        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Message not sent to queue.');
        $message->getId();

        self::assertNull($message->getEvent());
        self::assertNull($message->getError());
        self::assertNull($message->getRedeliveredAt());

        self::assertEquals(0, $message->getAttempts());
        self::assertEquals('my_queue', $message->getQueue());
        self::assertEquals($body, $message->getBody());
        self::assertEquals(Status::NEW, $message->getStatus());
        self::assertEquals(Priority::DEFAULT, $message->getPriority());
        self::assertEquals($time, $message->getExactTime());
        self::assertEquals(date('Y-m-d H:i:s', $time), $message->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testCreateNewDefaultMessageWithCeil(): void
    {
        $body =  json_encode([], JSON_THROW_ON_ERROR);

        $time = time();
        $message = (new Message('my_queue', $body))
            ->changePriority(new Priority(Priority::LOW))
            ->setEvent('my_event')
            ->setRedeliveredAt(new DateTimeImmutable());

        self::assertEquals(0, $message->getAttempts());
        self::assertEquals('my_queue', $message->getQueue());
        self::assertEquals('my_event', $message->getEvent());
        self::assertEquals($body, $message->getBody());
        self::assertEquals(Priority::LOW, $message->getPriority());

        self::assertEquals(date('Y-m-d H:i:s', $time), $message->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testChangePriority(): void
    {
        $message = new Message('my_queue', '');
        $message->changePriority(new Priority(Priority::HIGH));

        self::assertEquals(Priority::HIGH, $message->getPriority());
    }

    public function testChangeQueue(): void
    {
        $message = new Message('my_queue', '');
        $message->changeQueue('new_queue');

        self::assertEquals('new_queue', $message->getQueue());
    }

    public function testSetEvent(): void
    {
        $message = new Message('my_queue', '');
        $message->setEvent('my_event');

        self::assertEquals('my_event', $message->getEvent());
    }

    public function testRedeliveredAt(): void
    {
        $redelivered = new DateTimeImmutable();

        $message = new Message('my_queue', '');
        $message->setRedeliveredAt($redelivered);

        self::assertTrue($message->isRedelivered());
        self::assertEquals($redelivered->format('Y-m-d H:i:s'), $message->getRedeliveredAt()->format('Y-m-d H:i:s'));
    }

}