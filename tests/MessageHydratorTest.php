<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use DateTimeImmutable;
use Simple\Queue\Message;
use PHPUnit\Framework\TestCase;
use Simple\Queue\MessageHydrator;
use Simple\Queue\Priority;
use Simple\Queue\Status;

/**
 * Class MessageHydratorTest
 * @package Simple\QueueTest
 */
class MessageHydratorTest extends TestCase
{
    public function testCloneMessage(): void
    {
        $message = $this->generateBaseMessage();

        $newMessage = (new MessageHydrator($message))->getMessage();

        self::assertNotEquals(spl_object_id($message), spl_object_id($newMessage));
    }

    public function testGetMessage(): void
    {
        $message = $this->generateBaseMessage();

        $newMessage = (new MessageHydrator($message))->getMessage();

        self::assertInstanceOf(Message::class, $newMessage);
    }

    public function testChangeStatus(): void
    {
        $message = $this->generateBaseMessage();

        $newMessage = (new MessageHydrator($message))
            ->changeStatus(Status::IN_PROCESS)
            ->getMessage();

        self::assertEquals(Status::NEW, $message->getStatus());
        self::assertEquals(Status::IN_PROCESS, $newMessage->getStatus());
    }

    public function testJobable(): void
    {
        $message = $this->generateBaseMessage();

        $newMessage = (new MessageHydrator($message))
            ->jobable()
            ->getMessage();

        self::assertFalse($message->isJob());
        self::assertTrue($newMessage->isJob());
    }

    public function testUnJobable(): void
    {
        $message = $this->generateBaseMessage();

        $newMessage = (new MessageHydrator($message))
            ->unJobable()
            ->getMessage();

        self::assertFalse($message->isJob());
        self::assertFalse($newMessage->isJob());
    }

    public function testSetError(): void
    {
        $message = $this->generateBaseMessage();

        $newMessage = (new MessageHydrator($message))
            ->setError('myError')
            ->getMessage();

        self::assertNull($message->getError());
        self::assertEquals('myError', $newMessage->getError());
    }

    public function testSetErrorWithNull(): void
    {
        $message = $this->generateBaseMessage();

        $newMessage = (new MessageHydrator($message))
            ->setError(null)
            ->getMessage();

        self::assertNull($message->getError());
        self::assertNull($newMessage->getError());
    }

    public function testIncreaseAttempt(): void
    {
        $message = $this->generateBaseMessage();

        $newMessage = (new MessageHydrator($message))
            ->increaseAttempt()
            ->getMessage();

        self::assertEquals(0, $message->getAttempts());
        self::assertEquals(1, $newMessage->getAttempts());
    }

    public function testCreateMessage(): void
    {
        $time = time();
        $date = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $message = MessageHydrator::createMessage([
            'queue' => 'my_queue',
            'event' => 'my_event',
            'is_job' => true,
            'body' => 'my_body',
            'error' => 'my_error',
            'attempts' => 7,
            'status' => Status::NEW,
            'priority' => Priority::DEFAULT,
            'exact_time' => $time,
            'created_at' => $date,
            'redelivered_at' => $date,
        ]);

        self::assertEquals('my_queue', $message->getQueue());
        self::assertEquals('my_event', $message->getEvent());
        self::assertTrue($message->isJob());
        self::assertEquals('my_body', $message->getBody());
        self::assertEquals('my_error', $message->getError());
        self::assertEquals(7, $message->getAttempts());
        self::assertEquals(Status::NEW, $message->getStatus());
        self::assertEquals(Priority::DEFAULT, $message->getPriority());
        self::assertEquals($time, $message->getExactTime());
        self::assertEquals($date, $message->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertEquals($date, $message->getRedeliveredAt()->format('Y-m-d H:i:s'));
    }

    /**
     * @return Message
     */
    private function generateBaseMessage(): Message
    {
        return new Message('default', 'my_data');
    }
}
