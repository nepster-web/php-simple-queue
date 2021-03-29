<?php

declare(strict_types=1);

namespace Simple\Queue;

use DateTimeImmutable;
use Simple\Queue\Store\StoreInterface;

/**
 * Class Producer
 *
 * Sending a message to the queue
 *
 * @package Simple\Queue
 */
class Producer
{
    /** @var StoreInterface */
    private StoreInterface $store;

    /** @var Config */
    private Config $config;

    /**
     * Producer constructor.
     * @param StoreInterface $store
     * @param Config|null $config
     */
    public function __construct(StoreInterface $store, ?Config $config = null)
    {
        $this->store = $store;
        $this->config = $config ?: Config::getDefault();
    }

    /**
     * Create new message
     *
     * @param string $queue
     * @param $body
     * @return Message
     * @throws QueueException
     */
    public function createMessage(string $queue, $body): Message
    {
        if (is_callable($body)) {
            throw new QueueException('The closure cannot be serialized.');
        }

        if (is_object($body) && method_exists($body, '__toString')) {
            $body = (string)$body;
        }

        if (is_object($body) || is_array($body)) {
            $body = $this->config->getSerializer()->serialize($body);
        }

        return new Message($queue, (string)$body);
    }

    /**
     * Redelivered a message to the queue
     *
     * @param Message $message
     * @return Message
     */
    public function makeRedeliveryMessage(Message $message): Message
    {
        $newStatus = ($message->getStatus() === Status::NEW || $message->getStatus() === Status::IN_PROCESS) ?
            Status::REDELIVERED :
            $message->getStatus();

        $redeliveredTime = (new DateTimeImmutable('now'))
            ->modify(sprintf('+%s seconds', $this->config->getRedeliveryTimeInSeconds()));

        if (
            $message->getRedeliveredAt() &&
            $message->getRedeliveredAt()->getTimestamp() > $redeliveredTime->getTimestamp()
        ) {
            $redeliveredTime = $message->getRedeliveredAt();
        }

        if ($newStatus === Status::FAILURE) {
            $redeliveredTime = null;
        }

        $redeliveredMessage = (new Message($message->getQueue(), $message->getBody()))
            ->changePriority($message->getPriority())
            ->withEvent($message->getEvent())
            ->changeRedeliveredAt($redeliveredTime);

        return (new MessageHydrator($redeliveredMessage))
            ->changeStatus($newStatus)
            ->jobable($message->isJob())
            ->setError($message->getError())
            ->changeAttempts($message->getAttempts() + 1)
            ->getMessage();
    }

    /**
     * Dispatch a job
     *
     * @param string $jobName
     * @param array $data
     * @throws QueueException
     */
    public function dispatch(string $jobName, array $data): void
    {
        $job = $this->config->getJob($jobName);

        $message = $this->createMessage($job->queue(), $data)
            ->withEvent($this->config->getJobAlias($jobName));

        $message = (new MessageHydrator($message))->jobable()->getMessage();

        $this->send($message);
    }

    /**
     * Send message to queue
     *
     * @param Message $message
     */
    public function send(Message $message): void
    {
        $this->store->send($message);
    }
}
