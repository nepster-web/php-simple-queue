<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use Doctrine\DBAL\Schema\Column;
use Simple\Queue\QueueTableCreator;
use Simple\QueueTest\Helper\MockConnection;
use Simple\QueueTest\Helper\MockSchemaManager;

/**
 * Class QueueTableCreatorTest
 * @package Simple\QueueTest
 */
class QueueTableCreatorTest extends TestCase
{
    public function testChangeTableName(): void
    {
        QueueTableCreator::changeTableName('my_table');

        self::assertEquals('my_table', QueueTableCreator::getTableName());
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

        $queueTableCreator = new QueueTableCreator($connection);

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
            'body' => 'text',
            'priority' => 'smallint',
            'error' => 'text',
            'redelivered_at' => 'datetime_immutable',
            'created_at' => 'datetime_immutable',
            'exact_time' => 'bigint',
        ];

        self::assertEquals($expected, $tableColumns);
    }

}
