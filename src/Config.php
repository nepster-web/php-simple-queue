<?php

declare(strict_types=1);

namespace Simple\Queue;

use Simple\Queue\Serializer\BaseSerializer;
use Simple\Queue\Serializer\SerializerInterface;

/**
 * Class Config
 * @package Simple\Queue
 */
class Config
{
    /** @var int */
    private int $redeliveryTimeInSeconds = 180;

    /** @var int */
    private int $numberOfAttemptsBeforeFailure = 5;

    /** @var array */
    private array $jobs = [];

    /** @var array */
    private array $processors = [];

    /** @var SerializerInterface|null */
    private ?SerializerInterface $serializer = null;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        if ($this->serializer === null) {
            $this->serializer = new BaseSerializer();
        }
    }

    /**
     * @return static
     */
    public static function getDefault(): self
    {
        return new self;
    }

    /**
     * @return int
     */
    public function getRedeliveryTimeInSeconds(): int
    {
        return $this->redeliveryTimeInSeconds;
    }

    /**
     * @return int
     */
    public function getNumberOfAttemptsBeforeFailure(): int
    {
        return $this->numberOfAttemptsBeforeFailure;
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function changeRedeliveryTimeInSeconds(int $seconds): self
    {
        $this->redeliveryTimeInSeconds = $seconds;

        return $this;
    }

    /**
     * @param int $attempt
     * @return $this
     */
    public function changeNumberOfAttemptsBeforeFailure(int $attempt): self
    {
        $this->numberOfAttemptsBeforeFailure = $attempt;

        return $this;
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    /**
     * @param SerializerInterface $serializer
     * @return $this
     */
    public function withSerializer(SerializerInterface $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * @param string $jobName
     * @param Job $job
     * @return $this
     * @throws ConfigException
     */
    public function registerJob(string $jobName, Job $job): self
    {
        if (isset($this->jobs[$jobName])) {
            throw new ConfigException(sprintf('Job "%s" is already registered.', $jobName));
        }

        if (class_exists($jobName) === false && (bool)preg_match('/^[a-zA-Z0-9_.-]*$/u', $jobName) === false) {
            throw new ConfigException(sprintf('Job alias "%s" contains invalid characters.', $jobName));
        }

        $this->jobs[$jobName] = $job;

        return $this;
    }

    /**
     * @return array
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * @param string $jobName
     * @return Job
     * @throws QueueException
     */
    public function getJob(string $jobName): Job
    {
        if ($this->hasJob($jobName) === false) {
            throw new QueueException(sprintf('Job "%s" not registered.', $jobName));
        }

        if (class_exists($jobName)) {
            foreach ($this->jobs as $jobAlias => $job) {
                if (is_a($job, $jobName)) {
                    return $job;
                }
            }
        }

        return $this->jobs[$jobName];
    }

    /**
     * @param string $jobName
     * @return bool
     */
    public function hasJob(string $jobName): bool
    {
        if (class_exists($jobName)) {
            foreach ($this->jobs as $jobAlias => $job) {
                if (is_a($job, $jobName)) {
                    return true;
                }
            }
        }

        return isset($this->jobs[$jobName]);
    }

    /**
     * @param string $jobName
     * @return string
     * @throws ConfigException
     */
    public function getJobAlias(string $jobName): string
    {
        if (isset($this->jobs[$jobName])) {
            return $jobName;
        }

        foreach ($this->jobs as $jobAlias => $job) {
            if (is_a($job, $jobName)) {
                return $jobAlias;
            }
        }

        throw new ConfigException(sprintf('Job "%s" not registered.', $jobName));
    }

    /**
     * @param string $queue
     * @param callable $processor
     * @return $this
     * @throws ConfigException
     */
    public function registerProcessor(string $queue, callable $processor): self
    {
        if ($this->hasProcessor($queue)) {
            throw new ConfigException(sprintf('Processor "%s" is already registered.', $queue));
        }

        $this->processors[$queue] = $processor;

        return $this;
    }

    /**
     * @return array
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * @param string $queue
     * @return callable
     * @throws ConfigException
     */
    public function getProcessor(string $queue): callable
    {
        if ($this->hasProcessor($queue) === false) {
            throw new ConfigException(sprintf('Processor "%s" not registered.', $queue));
        }

        return $this->processors[$queue];
    }

    /**
     * @param string $queue
     * @return bool
     */
    public function hasProcessor(string $queue): bool
    {
        return isset($this->processors[$queue]);
    }
}
