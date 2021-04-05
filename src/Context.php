<?php

declare(strict_types=1);

namespace Simple\Queue;

/**
 * Class Context
 * @package Simple\Queue
 */
class Context
{
    /** @var Producer */
    private Producer $producer;

    /** @var array */
    private array $data;

    /** @var Message */
    private Message $message;

    /**
     * Context constructor.
     * @param Producer $producer
     * @param Message $message
     * @param array $data
     */
    public function __construct(Producer $producer, Message $message, array $data)
    {
        $this->producer = $producer;
        $this->data = $data;
        $this->message = $message;
    }

    /**
     * @return Producer
     */
    public function getProducer(): Producer
    {
        return $this->producer;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
