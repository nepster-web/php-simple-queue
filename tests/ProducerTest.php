<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use stdClass;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Simple\Queue\Job;
use Simple\Queue\Config;
use Simple\Queue\Message;
use Simple\Queue\Consumer;
use Simple\Queue\Producer;
use InvalidArgumentException;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;
use Simple\QueueTest\Helper\MockConnection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Class ProducerTest
 * @package Simple\QueueTest
 */
class ProducerTest extends TestCase
{
    public function testSuccessSendMessage(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $producer = new Producer($connection);
        $producer->send($producer->createMessage('my_queue', ''));

        self::assertEquals('queue', ($connection::$data)['insert'][0]);
        self::assertTrue(Uuid::isValid(($connection::$data)['insert'][1]['id']));
        self::assertEquals([
            'id' => Types::GUID,
            'status' => Types::STRING,
            'created_at' => Types::DATETIME_IMMUTABLE,
            'redelivered_at' => Types::DATETIME_IMMUTABLE,
            'attempts' => Types::SMALLINT,
            'queue' => Types::STRING,
            'event' => Types::STRING,
            'body' => Types::TEXT,
            'priority' => Types::SMALLINT,
            'error' => Types::TEXT,
            'exact_time' => Types::BIGINT,
            'is_job' => Types::BOOLEAN,
        ], ($connection::$data)['insert'][2]);
    }

    /**
     * @throws \JsonException
     */
    public function testBodyAsObjectWithMethodToString(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $body = new class() {
            public string $data = 'my_data';

            public function __toString(): string
            {
                return json_encode(['data' => $this->data], JSON_THROW_ON_ERROR);
            }
        };

        $producer = new Producer($connection);
        $producer->send($producer->createMessage('my_queue', $body));

        self::assertEquals('queue', ($connection::$data)['insert'][0]);
        self::assertEquals('{"data":"my_data"}', ($connection::$data)['insert'][1]['body']);
    }

    public function testBodyAsArray(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $producer = new Producer($connection);
        $producer->send($producer->createMessage('my_queue', ['my_data']));

        self::assertEquals('queue', ($connection::$data)['insert'][0]);
        self::assertEquals(
            Config::getDefault()->getSerializer()->serialize(['my_data']),
            ($connection::$data)['insert'][1]['body']
        );
    }

    public function testDispatch(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $job = new class() extends Job {
            public function handle(Message $message, Producer $producer): string
            {
                return Consumer::ACK;
            }
        };

        $config = (new Config())->registerJobAlias('job', get_class($job));

        $producer = new Producer($connection, $config);
        $producer->dispatch('job', ['my_data']);

        self::assertEquals('queue', ($connection::$data)['insert'][0]);
        self::assertEquals(
            Config::getDefault()->getSerializer()->serialize(['my_data']),
            ($connection::$data)['insert'][1]['body']
        );
    }

    public function testFailureSendMessage(): void
    {
        $connection = $this->getMockConnectionWithInsert(0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The transport fails to send the message due to some internal error.');

        $producer = new Producer($connection);
        $producer->send(new Message('my_queue', ''));
    }

    public function testDispatchWithNonExistent(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $config = (new Config())->registerJobAlias('job', 'test');

        $producer = new Producer($connection, $config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A non-existent class "job" is declared in the config.');

        $producer->dispatch('job', ['my_data']);
    }

    public function testCallableBody(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The closure cannot be serialized.');

        (new Producer($connection))->createMessage('my_queue', static function (): void {
        });
    }

    public function testDispatchWithNonExistentJob(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Job class "%s" doesn\'t exist.', 'non_existent_job'));

        (new Producer($connection))->dispatch('non_existent_job', []);
    }

    public function testDispatchWithNonExistentJobByNotExtendsJobClass(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Job class "%s" doesn\'t extends "%s".', stdClass::class, Job::class));

        (new Producer($connection))->dispatch(stdClass::class, []);
    }

    /**
     * @param int $value
     * @return MockConnection
     */
    private function getMockConnectionWithInsert(int $value): MockConnection
    {
        return new class($value) extends MockConnection {
            private int $value;

            public function __construct(int $value, ?AbstractSchemaManager $abstractSchemaManager = null)
            {
                $this->value = $value;
                parent::__construct($abstractSchemaManager);
            }

            public function insert($table, array $data, array $types = []): int
            {
                self::$data['insert'] = [$table, $data, $types];

                return $this->value;
            }
        };
    }
}
