<?php

declare(strict_types=1);

namespace Simple\Queue;

use RuntimeException;
use InvalidArgumentException;

/**
 * Class Config
 * @package Simple\Queue
 */
class Config
{
    /** @var int in seconds */
    public int $redeliveryTimeInSeconds = 180;

    /** @var int */
    public int $numberOfAttemptsBeforeFailure = 5;

    /** @var array */
    public array $jobs = [];

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
        if (preg_match('/^[0-1a-zA-Z-._]$/mu', $alias) === false) {
            throw new InvalidArgumentException(sprintf('The job alias "%s" contains invalid characters.', $alias));
        }

        if (isset($this->jobs[$alias])) {
            throw new RuntimeException(sprintf('Job "%s" is already registered in the jobs.', $alias));
        }

        $this->jobs[$alias] = $class;

        return $this;
    }
}
