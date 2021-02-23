<?php

declare(strict_types=1);

namespace Simple\QueueTest\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Class MockConnection
 * @package Simple\QueueTest\Helper
 */
class MockConnection extends Connection
{
    /** @var array */
    public static array $data = [];

    /** @var AbstractSchemaManager */
    private AbstractSchemaManager $abstractSchemaManager;

    /**
     * @inheritDoc
     * @param AbstractSchemaManager|null $abstractSchemaManager
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(?AbstractSchemaManager $abstractSchemaManager = null)
    {
        $this->abstractSchemaManager = $abstractSchemaManager ?: new MockSchemaManager();

        $driver = new class extends AbstractSQLiteDriver {
            public function connect(array $params): void
            {
            }
        };

        parent::__construct([], $driver);
    }

    /**
     * @inheritDoc
     */
    public function insert($table, array $data, array $types = []): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaManager(): AbstractSchemaManager
    {
        return $this->abstractSchemaManager;
    }
}
