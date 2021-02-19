<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use Simple\Queue\QueueTableCreator;
use Simple\QueueTest\Helper\FakeConnectionTrait;

/**
 * Class QueueTableCreatorTest
 * @package Simple\QueueTest
 */
class QueueTableCreatorTest extends TestCase
{
    use FakeConnectionTrait;

    public function testChangeTableName(): void
    {
        QueueTableCreator::changeTableName('my_table');

        self::assertEquals('my_table', QueueTableCreator::getTableName());
    }

    public function testSimulateTableCreation(): void
    {
        $data = [];

        $queueTableCreator = new QueueTableCreator($this->getFakeConnection($data));

        $queueTableCreator->createDataBaseTable();

        /** @var Table $table */
        $table = $data['createTable'];

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
