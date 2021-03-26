<?php

declare(strict_types=1);

namespace Simple\Queue\Store;

use Simple\Queue\Status;
use Simple\Queue\Message;

/**
 * Interface StoreInterface
 * @package Simple\Queue\Store
 */
interface StoreInterface
{
    /**
     * Store initialization
     */
    public function init(): void;

    /**
     * Send message to queue
     *
     * @param Message $message
     */
    public function send(Message $message): void;

    /**
     * Fetch the next message from the queue
     *
     * @param array $queues
     * @return Message|null
     */
    public function fetchMessage(array $queues = []): ?Message;

    /**
     * Change message status from queue
     *
     * @param Message $message
     * @param Status $status
     */
    public function changeMessageStatus(Message $message, Status $status): void;

    /**
     * Delete message from queue
     *
     * @param Message $message
     */
    public function deleteMessage(Message $message): void;
}
