<?php

declare(strict_types=1);

namespace Simple\Queue;

use Exception;
use DateTimeImmutable;
use Laminas\Hydrator\ReflectionHydrator;
use Laminas\Hydrator\Strategy\HydratorStrategy;

/**
 * Class MessageHydrator
 *
 * Hydrator which changes system data
 *
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
        $this->message = clone $message;
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
     * @return $this
     */
    public function jobable(): self
    {
        $this->message = $this->hydrate(['isJob' => true]);

        return $this;
    }

    /**
     * @return $this
     */
    public function unJobable(): self
    {
        $this->message = $this->hydrate(['isJob' => false]);

        return $this;
    }

    /**
     * @param string|null $error
     * @return $this
     */
    public function setError(?string $error): self
    {
        $this->message = $this->hydrate(['error' => $error]);

        return $this;
    }

    /**
     * @return $this
     */
    public function increaseAttempt(): self
    {
        $this->hydrate(['attempts' => $this->message->getAttempts() + 1]);

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
        /** @var Message $redeliveredMessage */
        $redeliveredMessage = (new ReflectionHydrator())->hydrate($data, $this->message);

        return $redeliveredMessage;
    }

    /**
     * Create entity Message from array data
     *
     * @param array $data
     * @return Message
     * @throws Exception
     */
    public static function createMessage(array $data): Message
    {
        $strategy = new HydratorStrategy(new ReflectionHydrator(), Message::class);

        /** @var Message $message */
        $message = $strategy->hydrate(array_merge($data, [
            'queue' => $data['queue'] ?? 'default',
            'event' => $data['event'] ?? null,
            'isJob' => $data['is_job'] ?? false,
            'body' => $data['body'] ?? '',
            'error' => $data['error'] ?? null,
            'attempts' => $data['attempts'] ?? 0,
            'status' => new Status($data['status'] ?? Status::NEW),
            'priority' => new Priority((int)($data['priority'] ?? Priority::DEFAULT)),
            'exactTime' => $data['exact_time'] ?? time(),
            'createdAt' => new DateTimeImmutable($data['created_at'] ?? 'now'),
            'redeliveredAt' => isset($data['redelivered_at']) ? new DateTimeImmutable($data['redelivered_at']) : null,
        ]));

        return $message;
    }
}
