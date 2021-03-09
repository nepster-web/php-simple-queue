<?php

declare(strict_types=1);

use Simple\Queue\Consumer;
use Simple\Queue\Message;
use Simple\Queue\Producer;

/**
 * Class ExampleJob
 * @package Simple\Queue
 */
class ExampleJob extends \Simple\Queue\Job
{
    /**
     * @param Message $message
     * @param Producer $producer
     * @return string
     */
    public function handle(Message $message, Producer $producer): string
    {
        // One of the following results must be returned: Consumer::ACK, Consumer::REJECT or Consumer::REQUEUE.
        var_dump($message->getBody() . PHP_EOL);

        return Consumer::ACK;
    }
}
