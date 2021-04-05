<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use Exception;
use Simple\Queue\Job;
use DateTimeImmutable;
use Simple\Queue\Config;
use Simple\Queue\Status;
use Simple\Queue\Context;
use Simple\Queue\Message;
use Simple\Queue\Consumer;
use Simple\Queue\Producer;
use PHPUnit\Framework\TestCase;
use Simple\Queue\QueueException;
use Simple\Queue\MessageHydrator;
use Simple\QueueTest\Helper\MockConnection;
use Simple\Queue\Transport\TransportException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Simple\Queue\Transport\DoctrineDbalTransport;

/**
 * Class ProducerTest
 * @package Simple\QueueTest
 */
class ProducerTest extends TestCase
{
    public function testSuccessSendMessage(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new class($connection) extends DoctrineDbalTransport {
            public static Message $message;

            public function send(Message $message): void
            {
                self::$message = $message;
            }
        };

        $producer = new Producer($transport);
        $message = $producer->createMessage('my_queue', '');

        $producer->send($message);

        self::assertEquals($transport::$message, $message);
    }

    public function testBodyAsObjectWithMethodToString(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $body = new class() {
            public string $data = 'my_data';

            public function __toString(): string
            {
                return json_encode(['data' => $this->data], JSON_THROW_ON_ERROR);
            }
        };

        $producer = new Producer($transport);
        $producer->send($producer->createMessage('my_queue', $body));

        self::assertEquals('queue', ($connection::$data)['insert'][0]);
        self::assertEquals('{"data":"my_data"}', ($connection::$data)['insert'][1]['body']);
    }

    public function testBodyAsArray(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $producer = new Producer($transport);
        $producer->send($producer->createMessage('my_queue', ['my_data']));

        self::assertEquals('queue', ($connection::$data)['insert'][0]);
        self::assertEquals(
            Config::getDefault()->getSerializer()->serialize(['my_data']),
            ($connection::$data)['insert'][1]['body']
        );
    }

    public function testCallableBody(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $this->expectException(QueueException::class);
        $this->expectExceptionMessage('The closure cannot be serialized.');

        (new Producer($transport))->createMessage('my_queue', static function (): void {
        });
    }

    public function testFailureSendMessage(): void
    {
        $connection = $this->getMockConnectionWithInsert(0);

        $transport = new DoctrineDbalTransport($connection);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('The transport fails to send the message due to some internal error.');

        $producer = new Producer($transport);
        $producer->send(new Message('my_queue', ''));
    }

    public function testDispatch(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $job = new class() extends Job {
            public function handle(Context $context): string
            {
                return Consumer::STATUS_ACK;
            }
        };

        $config = (new Config())->registerJob('job', $job);

        $producer = new Producer($transport, $config);
        $producer->dispatch('job', ['my_data']);

        self::assertEquals('queue', ($connection::$data)['insert'][0]);
        self::assertEquals(
            Config::getDefault()->getSerializer()->serialize(['my_data']),
            ($connection::$data)['insert'][1]['body']
        );
    }

    public function testDispatchWithNoRegisteredJob(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $this->expectException(QueueException::class);
        $this->expectExceptionMessage(sprintf('Job "%s" not registered.', 'non_registered_job'));

        (new Producer($transport))->dispatch('non_registered_job', []);
    }

    public function testDefaultMakeRedeliveryMessage(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $message = new Message('my_queue', '');

        $redeliveryMessage = (new Producer($transport))->makeRedeliveryMessage($message);

        self::assertEquals(Status::REDELIVERED, $redeliveryMessage->getStatus());
        self::assertNotNull($redeliveryMessage->getRedeliveredAt());
        self::assertEquals($message->getAttempts() + 1, $redeliveryMessage->getAttempts());
    }

    public function testMakeRedeliveryMessageWithData(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $message = (new MessageHydrator(new Message('my_queue', '')))
            ->changeStatus(Status::IN_PROCESS)
            ->setError((string)(new Exception('My error')))
            ->jobable(true)
            ->changeAttempts(100)
            ->getMessage();

        $redeliveryMessage = (new Producer($transport))->makeRedeliveryMessage($message);

        self::assertEquals(Status::REDELIVERED, $redeliveryMessage->getStatus());
        self::assertNotNull($redeliveryMessage->getError());
        self::assertTrue($redeliveryMessage->isJob());
        self::assertEquals(100 + 1, $redeliveryMessage->getAttempts());
        self::assertNotNull($redeliveryMessage->getRedeliveredAt());
        self::assertEquals('my_queue', $redeliveryMessage->getQueue());
    }

    public function testMakeRedeliveryMessageWithStatusFailure(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $message = (new MessageHydrator(new Message('my_queue', '')))
            ->changeStatus(Status::FAILURE)
            ->getMessage();

        $redeliveryMessage = (new Producer($transport))->makeRedeliveryMessage($message);

        self::assertEquals(Status::FAILURE, $redeliveryMessage->getStatus());
        self::assertNull($redeliveryMessage->getRedeliveredAt());
    }

    public function testMakeRedeliveryMessageWithRedeliveredTimeInPast(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $redeliveredAt = (new \DateTimeImmutable())->modify('-10 minutes');

        $message = (new Message('my_queue', ''))->changeRedeliveredAt($redeliveredAt);

        $redeliveryMessage = (new Producer($transport))->makeRedeliveryMessage($message);

        $redeliveredTime = (new DateTimeImmutable('now'))
            ->modify(sprintf('+%s seconds', Config::getDefault()->getRedeliveryTimeInSeconds()));

        self::assertEquals(
            $redeliveredTime->format('Y-m-d H:i:s'),
            $redeliveryMessage->getRedeliveredAt()->format('Y-m-d H:i:s')
        );
    }

    public function testMakeRedeliveryMessageWithRedeliveredTimeInFuture(): void
    {
        $connection = $this->getMockConnectionWithInsert(1);

        $transport = new DoctrineDbalTransport($connection);

        $redeliveredAt = (new \DateTimeImmutable())->modify('+10 minutes');

        $message = (new Message('my_queue', ''))->changeRedeliveredAt($redeliveredAt);

        $redeliveryMessage = (new Producer($transport))->makeRedeliveryMessage($message);

        self::assertEquals(
            $redeliveredAt->format('Y-m-d H:i:s'),
            $redeliveryMessage->getRedeliveredAt()->format('Y-m-d H:i:s')
        );
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
