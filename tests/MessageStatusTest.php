<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use Simple\Queue\Status;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageStatusTest
 * @package Simple\QueueTest
 */
class MessageStatusTest extends TestCase
{
    public function testNewStatus(): void
    {
        $status = new Status(Status::NEW);

        self::assertEquals(Status::NEW, $status->getValue());
    }

    public function testProcessStatus(): void
    {
        $status = new Status(Status::IN_PROCESS);

        self::assertEquals(Status::IN_PROCESS, $status->getValue());
    }

    public function testRedeliveredStatus(): void
    {
        $status = new Status(Status::REDELIVERED);

        self::assertEquals(Status::REDELIVERED, $status->getValue());
    }

    public function testErrorStatus(): void
    {
        $status = new Status(Status::ERROR);

        self::assertEquals(Status::ERROR, $status->getValue());
    }

    public function testAnotherStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('"%s" is not a valid message status.', 'my_status'));

        new Status('my_status');
    }

    public function testStatusToString(): void
    {
        $status = new Status(Status::NEW);

        self::assertEquals(Status::NEW, (string)$status);
    }
}
