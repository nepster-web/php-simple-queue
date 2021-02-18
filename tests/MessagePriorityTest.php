<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Simple\Queue\Priority;

/**
 * Class MessagePriorityTest
 * @package Simple\QueueTest
 */
class MessagePriorityTest extends TestCase
{
    public function testDefaultPriority(): void
    {
        $priority = new Priority(Priority::DEFAULT);

        self::assertEquals(Priority::DEFAULT, $priority->getValue());
    }

    public function testVeryLowPriority(): void
    {
        $priority = new Priority(Priority::VERY_LOW);

        self::assertEquals(Priority::VERY_LOW, $priority->getValue());
    }

    public function testLowPriority(): void
    {
        $priority = new Priority(Priority::LOW);

        self::assertEquals(Priority::LOW, $priority->getValue());
    }

    public function testHighPriority(): void
    {
        $priority = new Priority(Priority::HIGH);

        self::assertEquals(Priority::HIGH, $priority->getValue());
    }

    public function testVeryHighPriority(): void
    {
        $priority = new Priority(Priority::VERY_HIGH);

        self::assertEquals(Priority::VERY_HIGH, $priority->getValue());
    }

    public function testAnotherPriority(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('"%s" is not a valid message priority.', 777));

        new Priority(777);
    }

    public function testPriorityToString(): void
    {
        $priority = new Priority(Priority::VERY_LOW);

        self::assertEquals(Priority::VERY_LOW, (int)(string)$priority);
    }
}
