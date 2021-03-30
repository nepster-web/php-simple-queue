<?php

declare(strict_types=1);

namespace Simple\QueueTest\Helper;

/**
 * Class DBALDriverResult
 * @package Simple\QueueTest\Helper
 */
class DBALDriverResult implements \Doctrine\DBAL\Driver\Result
{
    /** @var array */
    private array $source = [];

    /**
     * DBALDriverResult constructor.
     * @param array $source
     */
    public function __construct(array $source = [])
    {
        $this->source = $source;
    }

    public function fetchNumeric(): bool
    {
        return false;
    }

    public function fetchAssociative(): array
    {
        return $this->source['fetchAssociative'] ?? [];
    }

    public function fetchOne(): array
    {
        return [];
    }

    public function fetchAllNumeric(): array
    {
        return [];
    }

    public function fetchAllAssociative(): array
    {
        return [];
    }

    public function fetchFirstColumn(): array
    {
        return [];
    }

    public function rowCount(): int
    {
        return 0;
    }

    public function columnCount(): int
    {
        return 0;
    }

    public function free(): void
    {
    }
}
