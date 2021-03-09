<?php

declare(strict_types=1);

namespace Simple\Queue;

use LogicException;
use DateTimeImmutable;

/**
 * Class Message
 * @package Simple\Queue
 */
class Message
{
    /**
     * Message ID
     * Not set if the message is not send to the database
     *
     * @var string|null
     */
    private ?string $id = null;

    /**
     * Message status in the queue
     * (The message will be deleted from db if successfully processed)
     *
     * @var Status
     */
    private Status $status;

    /**
     * Queue name
     *
     * @var string
     */
    private string $queue;

    /**
     * Event name
     * May be empty. Necessary for a more detailed separation of consumer processing
     *
     * @var string|null
     */
    private ?string $event;

    /** @var bool */
    private bool $isJob;

    /**
     * Message data
     * For example: JSON, Serialized string etc.
     *
     * @var string
     */
    private string $body;

    /**
     * Processing priority
     * May affect the sequence of message processing
     *
     * @var Priority
     */
    private Priority $priority;

    /**
     * Number of attempts before considering processing a failure
     *
     * @var int
     */
    private int $attempts;

    /**
     * Error information during processing
     *
     * @var string|null
     */
    private ?string $error;

    /**
     * @var int
     */
    private int $exactTime;

    /**
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $redeliveredAt;

    /**
     * Message constructor.
     * @param string $queue
     * @param string $body
     */
    public function __construct(string $queue, string $body)
    {
        $this->status = new Status(Status::NEW);
        $this->queue = $queue;
        $this->body = $body;
        $this->priority = new Priority(Priority::DEFAULT);
        $this->attempts = 0;
        $this->error = null;
        $this->event = null;
        $this->isJob = false;
        $this->exactTime = time();
        $this->createdAt = new DateTimeImmutable('now');
        $this->redeliveredAt = null;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        if ($this->id === null) {
            throw new LogicException('The message has no id. It looks like it was not sent to the queue.');
        }

        return $this->id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return (string)$this->status;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @return int
     */
    public function getExactTime(): int
    {
        return $this->exactTime;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @return string|null
     */
    public function getEvent(): ?string
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return (int)((string)$this->priority);
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getRedeliveredAt(): ?DateTimeImmutable
    {
        return $this->redeliveredAt;
    }

    /**
     * @return bool
     */
    public function isRedelivered(): bool
    {
        if ((string)$this->status === Status::REDELIVERED) {
            return true;
        }

        return $this->redeliveredAt ? true : false;
    }

    /**
     * @return bool
     */
    public function isJob(): bool
    {
        return $this->isJob;
    }

    /**
     * @param DateTimeImmutable|null $redeliveredAt
     * @return $this
     */
    public function setRedeliveredAt(?DateTimeImmutable $redeliveredAt): self
    {
        $this->redeliveredAt = $redeliveredAt;

        return $this;
    }

    /**
     * @param string $queue
     * @return $this
     */
    public function changeQueue(string $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @param Priority $priority
     * @return $this
     */
    public function changePriority(Priority $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @param string|null $event
     * @return $this
     */
    public function setEvent(?string $event): self
    {
        $this->event = $event;

        return $this;
    }
}
