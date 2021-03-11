<?php

declare(strict_types=1);

namespace Simple\Queue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\Table;

/**
 * Class QueueTableCreator
 * @package Simple\Queue
 */
class QueueTableCreator
{
    /** @var Connection */
    protected Connection $connection;

    /** @var string */
    protected static string $tableName = 'queue';

    /**
     * QueueTableCreator constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $tableName
     */
    public static function changeTableName(string $tableName): void
    {
        self::$tableName = $tableName;
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return self::$tableName;
    }

    /**
     * Creating a queue table
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function createDataBaseTable(): void
    {
        $schemaManager = $this->connection->getSchemaManager();

        $tableExists = $schemaManager ? $schemaManager->tablesExist([self::getTableName()]) : false;

        if ($schemaManager === null || $tableExists === true) {
            return;
        }

        $table = new Table(self::getTableName());

        $table->addColumn('id', Types::GUID, ['length' => 16, 'fixed' => true]);
        $table->addColumn('status', Types::STRING);
        $table->addColumn('attempts', Types::SMALLINT);
        $table->addColumn('queue', Types::STRING);
        $table->addColumn('event', Types::STRING, ['notnull' => false]);
        $table->addColumn('is_job', Types::BOOLEAN, ['default' => false]);
        $table->addColumn('body', Types::TEXT, ['notnull' => false]);
        $table->addColumn('priority', Types::SMALLINT, ['notnull' => false]);
        $table->addColumn('error', Types::TEXT, ['notnull' => false]);
        $table->addColumn('redelivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('exact_time', Types::BIGINT);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['priority', 'created_at', 'queue', 'status', 'event', 'id']);

        $schemaManager->createTable($table);
    }
}
