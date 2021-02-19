<?php

declare(strict_types=1);

namespace Simple\QueueTest\Helper;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Class MockSchemaManager
 * @package Simple\QueueTest\Helper
 */
class MockSchemaManager extends AbstractSchemaManager
{
    /** @var array */
    public static array $data = [];

    /**
     * @inheritDoc
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct()
    {
        parent::__construct(new MockConnection($this), new SqlitePlatform());
    }

    /**
     * @inheritDoc
     */
    public function tablesExist($names): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function createTable(Table $table): void
    {

    }

    /**
     * @inheritDoc
     */
    protected function _getPortableTableColumnDefinition($tableColumn): Column
    {
        return new Column('default', new StringType());
    }
}