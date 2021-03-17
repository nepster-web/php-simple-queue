<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use Simple\Queue\Job;
use Simple\Queue\Message;
use Simple\Queue\Consumer;
use Simple\Queue\Producer;
use PHPUnit\Framework\TestCase;

/**
 * Class JobTest
 * @package Simple\QueueTest
 */
class JobTest extends TestCase
{
    public function testDefaultQueue(): void
    {
        $job = $this->generateBaseJob();

        self::assertEquals('default', $job->queue());
    }

    public function testDefaultAttempts(): void
    {
        $job = $this->generateBaseJob();

        self::assertNull($job->attempts());
    }

    /**
     * @return Job
     */
    private function generateBaseJob(): Job
    {
        return new class extends Job {
            public function handle(Message $message, Producer $producer): string
            {
                return Consumer::STATUS_ACK;
            }
        };
    }
}
