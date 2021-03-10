<?php

declare(strict_types=1);

namespace Simple\Queue;

use RuntimeException;
use InvalidArgumentException;
use Simple\Queue\Serializer\SymfonySerializer;
use Simple\Queue\Serializer\SerializerInterface;

/**
 * Class Config
 * @package Simple\Queue
 */
class Config
{
    /** @var int in seconds */
    private int $redeliveryTimeInSeconds = 180;

    /** @var int */
    private int $numberOfAttemptsBeforeFailure = 5;

    /** @var array */
    private array $jobs = [];

    /** @var SerializerInterface|null */
    private ?SerializerInterface $serializer = null;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        if ($this->serializer === null) {
            $this->serializer = new SymfonySerializer();
        }
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
     * @return array
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getJob(string $key): ?string
    {
        if ($this->hasJob($key) === false) {
            throw new InvalidArgumentException(sprintf('Job "%s" doesn\'t exists.', $key));
        }

        return $this->jobs[$key];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasJob(string $key): bool
    {
        return isset($this->jobs[$key]);
    }

    /**
     * @return static
     */
    public static function getDefault(): self
    {
        return new self;
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function changeRedeliveryTime(int $seconds): self
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
     * @param string $alias
     * @param string $class
     * @return $this
     */
    public function registerJobAlias(string $alias, string $class): self
    {
        if ((bool)preg_match('/^[a-zA-Z0-9_.-]*$/u', $alias) === false) {
            throw new InvalidArgumentException(sprintf('The job alias "%s" contains invalid characters.', $alias));
        }

        if (isset($this->jobs[$alias])) {
            throw new RuntimeException(sprintf('Job "%s" is already registered in the jobs.', $alias));
        }

        $this->jobs[$alias] = $class;

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
}
