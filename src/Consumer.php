<?php

declare(strict_types=1);

namespace Simple\Queue;

use Throwable;
use InvalidArgumentException;
use Simple\Queue\Store\StoreInterface;

/**
 * Class Consumer
 *
 * TODO: 1 - create job instance
 * TODO: 2 - move db operations to other class
 * TODO: 3 - dispatch job
 * TODO: 4 - deserialize data to job and processor
 *
 * @package Simple\Queue
 */
class Consumer
{
    /** Use to mark results as successful */
    public const STATUS_ACK = 'ACK';

    /** Use one more try if necessary */
    public const STATUS_REJECT = 'REJECT';

    /** Use in case of a delayed queue */
    public const STATUS_REQUEUE = 'REQUEUE';

    /** @var StoreInterface */
    protected StoreInterface $store;

    /** @var Producer */
    protected Producer $producer;

    /** @var Config|null */
    private ?Config $config;

    /**
     * Consumer constructor.
     * @param StoreInterface $store
     * @param Producer $producer
     * @param Config|null $config
     */
    public function __construct(StoreInterface $store, Producer $producer, ?Config $config = null)
    {
        $this->store = $store;
        $this->producer = $producer;
        $this->config = $config ?: Config::getDefault();
    }

    /**
     * The message has been successfully processed and will be removed from the queue
     *
     * @param Message $message
     */
    public function acknowledge(Message $message): void
    {
        $this->store->deleteMessage($message);
    }

    /**
     * Reject message with requeue option
     *
     * @param Message $message
     * @param bool $requeue
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        $this->acknowledge($message);

        if ($requeue) {
            $redeliveryMessage = $this->producer->makeRedeliveryMessage($message);
            $this->producer->send($redeliveryMessage);
        }
    }

    /**
     * TODO: pass the processing message status to $eachCallback
     *
     * @param array $queues
     * @param callable|null $eachCallback
     */
    public function consume(array $queues = [], ?callable $eachCallback = null): void
    {
        $this->store->init();

        while (true) {
            if ($message = $this->store->fetchMessage($queues)) {
                try {
                    $this->processing($message);
                } catch (Throwable $throwable) {
                    try {
                        $this->processFailureResult($throwable, $message);
                    } catch (\Doctrine\DBAL\Exception $exception) {
                        // maybe lucky later
                    }
                }
                $eachCallback && $eachCallback($message, $throwable ?? null);
                continue;
            }
            usleep(200000); // 0.2 second
        }
    }

    /**
     * @param Message $message
     */
    protected function processing(Message $message): void
    {
        $this->store->changeMessageStatus($message, new Status(Status::IN_PROCESS));

        if ($message->isJob()) {
            try {

                $job = $this->config->getJob($message->getEvent());

                $result = $job->handle($message, $this->producer);

                $this->processSuccessResult($result, $message);

            } catch (Throwable $exception) {
                $this->processFailureResult($exception, $message);
            }
            return;
        }

        if ($this->config->hasProcessor($message->getQueue())) {
            try {

                $result = $this->config->getProcessor($message->getQueue())($message, $this->producer);

                $this->processSuccessResult($result, $message);

            } catch (Throwable $exception) {
                $this->processFailureResult($exception, $message);
            }
            return;
        }

        $this->processUndefinedHandlerResult($message);
    }

    /**
     * @param Message $message
     */
    protected function processUndefinedHandlerResult(Message $message): void
    {
        MessageHydrator::changeProperty($message, 'status', new Status(Status::UNDEFINED_HANDLER));
        MessageHydrator::changeProperty($message, 'error', 'Could not find any job or processor.');

        $this->reject($message, true);
    }

    /**
     * @param Throwable $exception
     * @param Message $message
     */
    protected function processFailureResult(Throwable $exception, Message $message): void
    {
        $newStatus = Status::REDELIVERED;

        $numberOfAttemptsBeforeFailure = $this->config->getNumberOfAttemptsBeforeFailure();

        if ($message->isJob()) {
            $job = $this->config->getJob($message->getEvent());
            if ($job->attempts()) {
                $numberOfAttemptsBeforeFailure = $job->attempts();
            }
        }

        if (($message->getAttempts() + 1) >= $numberOfAttemptsBeforeFailure) {
            $newStatus = Status::FAILURE;
        }

        MessageHydrator::changeProperty($message, 'status', new Status($newStatus));
        MessageHydrator::changeProperty($message, 'error', (string)$exception);

        $this->reject($message, true);
    }

    /**
     * @param string $status
     * @param Message $message
     */
    protected function processSuccessResult(string $status, Message $message): void
    {
        if ($status === self::STATUS_ACK) {
            $this->acknowledge($message);

            return;
        }

        if ($status === self::STATUS_REJECT) {
            $this->reject($message);

            return;
        }

        if ($status === self::STATUS_REQUEUE) {
            $this->reject($message, true);

            return;
        }

        throw new InvalidArgumentException(sprintf('Unsupported result status: "%s".', $status));
    }
}
