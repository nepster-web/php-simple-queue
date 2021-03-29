<?php

declare(strict_types=1);

namespace Simple\QueueTest\Store;

use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\Column;
use Simple\QueueTest\Helper\MockConnection;
use Simple\QueueTest\Helper\MockSchemaManager;
use Simple\Queue\Store\DoctrineDbalTableCreator;

/**
 * Class QueueTableCreatorTest
 * @package Simple\QueueTest\Store
 */
class QueueTableCreatorTest extends TestCase
{
    public function testChangeTableName(): void
    {
        DoctrineDbalTableCreator::changeTableName('my_table');

        self::assertEquals('my_table', DoctrineDbalTableCreator::getTableName());
    }

    public function testSimulateTableCreation(): void
    {
        $data = [];

        $schemaManager = new class extends MockSchemaManager {
            public function createTable(Table $table): void
            {
                self::$data['createTable'] = $table;
            }
        };
        $connection = new MockConnection($schemaManager);

        $queueTableCreator = new DoctrineDbalTableCreator($connection);

        $queueTableCreator->createDataBaseTable();

        /** @var Table $table */
        $table = $schemaManager::$data['createTable'];

        $tableColumns = [];

        /** @var Column $column */
        foreach ($table->getColumns() as $name => $column) {
            $tableColumns[$name] = $column->getType()->getName();
        }

        $expected = [
            'id' => 'guid',
            'status' => 'string',
            'attempts' => 'smallint',
            'queue' => 'string',
            'event' => 'string',
            'is_job' => 'boolean',
            'body' => 'text',
            'priority' => 'smallint',
            'error' => 'text',
            'redelivered_at' => 'datetime_immutable',
            'created_at' => 'datetime_immutable',
            'exact_time' => 'bigint',
        ];

        self::assertEquals($expected, $tableColumns);
    }

    public function testSimulateTableCreationWithoutTableCrate(): void
    {
        $data = [];

        $schemaManager = new class extends MockSchemaManager {
            public function tablesExist($names): bool
            {
                self::$data['tablesExist'] = true;

                return true;
            }
        };
        $connection = new MockConnection($schemaManager);

        $queueTableCreator = new DoctrineDbalTableCreator($connection);

        $queueTableCreator->createDataBaseTable();

        $tablesExist = $schemaManager::$data['tablesExist'];

        self::assertTrue($tablesExist);
    }
}
