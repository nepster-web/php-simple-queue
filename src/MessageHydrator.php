<?php

declare(strict_types=1);

namespace Simple\Queue;

use Laminas\Hydrator\ReflectionHydrator;

/**
 * Class MessageHydrator
 * @package Simple\Queue
 */
class MessageHydrator
{
    /** @var Message */
    private Message $message;

    /**
     * MessageHydrator constructor.
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @param string $status
     * @return MessageHydrator
     */
    public function changeStatus(string $status): self
    {
        $this->message = $this->hydrate(['status' => new Status($status)]);

        return $this;
    }

    /**
     * @param string $error
     * @return $this
     */
    public function changeError(string $error): self
    {
        $this->message = $this->hydrate(['error' => $error]);

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @param array $data
     * @return Message
     */
    private function hydrate(array $data): Message
    {
        $hydrator = new ReflectionHydrator();

        /** @var Message $redeliveredMessage */
        $redeliveredMessage = $hydrator->hydrate($data, $this->message);

        return $redeliveredMessage;
    }
}
