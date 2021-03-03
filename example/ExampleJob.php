<?php

declare(strict_types=1);

namespace Simple\Queue;

/**
 * Class ExampleJob
 * @package Simple\Queue
 */
class ExampleJob extends Job
{
    /**
     * @param Message $message
     * @param Producer $producer
     * @return string
     */
    public function handle(Message $message, Producer $producer): string
    {
        // One of the following results must be returned: Consumer::ACK, Consumer::REJECT or Consumer::REQUEUE.
        return Consumer::ACK;
    }
}
