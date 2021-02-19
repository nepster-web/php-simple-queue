<?php

declare(strict_types=1);

namespace Simple\QueueTest\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\StringType;

/**
 * Trait FakeConnectionTrait
 * @package Simple\QueueTest\Helper
 */
trait FakeConnectionTrait
{
    /**
     * Connection mock
     *
     * @param array $data
     * @return Connection
     */
    public function getFakeConnection(array &$data = []): Connection
    {
        $driver = new class extends AbstractSQLiteDriver {
            public function connect(array $params)
            {
            }
        };

        return new class ([], $driver, $data) extends Connection {
            private array $data = [];
            public function __construct(array $params, Driver $driver, &$data)
            {
                $this->data = &$data;
                parent::__construct($params, $driver);
            }
            public function getSchemaManager(): AbstractSchemaManager
            {
                return new class ($this, new SqlitePlatform(), $this->data) extends AbstractSchemaManager {
                    private array $data = [];
                    public function __construct(Connection $connection, AbstractPlatform $platform, &$data)
                    {
                        $this->data = &$data;
                        parent::__construct($connection, $platform);
                    }

                    public function tablesExist($names): bool
                    {
                        return false;
                    }
                    public function createTable(Table $table): void
                    {
                        $this->data['createTable'] = $table;
                    }
                    protected function _getPortableTableColumnDefinition($tableColumn): Column
                    {
                        return new Column('default', new StringType());
                    }
                };
            }
        };
    }
}