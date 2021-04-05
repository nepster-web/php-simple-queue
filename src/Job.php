<?php

declare(strict_types=1);

namespace Simple\Queue;

/**
 * Class Job
 * @package Simple\Queue
 */
abstract class Job
{
    /**
     * @param Context $context
     * @return string
     */
    abstract public function handle(Context $context): string;

    /**
     * @return string
     */
    public function queue(): string
    {
        return 'default';
    }

    /**
     * @return int|null
     */
    public function attempts(): ?int
    {
        return null;
    }
}
