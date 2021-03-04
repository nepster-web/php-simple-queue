<?php

declare(strict_types=1);

namespace Simple\Queue;

/**
 * Class Job
 * @package Simple\Queue
 */
abstract class Job
{
    /**
     * @param Message $message
     * @param Producer $producer
     * @return mixed
     */
    abstract public function handle(Message $message, Producer $producer): string;

    /** @return string */
    public function getQueue(): string
    {
        return Consumer::ACK;
    }
}
