<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use Simple\Queue\Job;
use Simple\Queue\Config;
use Simple\Queue\Message;
use Simple\Queue\Consumer;
use Simple\Queue\Producer;
use PHPUnit\Framework\TestCase;
use Simple\Queue\QueueException;
use Simple\Queue\ConfigException;
use Simple\Queue\Serializer\BaseSerializer;
use Simple\Queue\Serializer\SerializerInterface;

/**
 * Class ConfigTest
 * @package Simple\QueueTest
 */
class ConfigTest extends TestCase
{
    public function testSuccess(): void
    {
        $config = (new Config())
            ->changeRedeliveryTimeInSeconds(250)
            ->changeNumberOfAttemptsBeforeFailure(4)
            ->withSerializer($this->createMock(SerializerInterface::class));

        self::assertEquals(250, $config->getRedeliveryTimeInSeconds());
        self::assertEquals(4, $config->getNumberOfAttemptsBeforeFailure());
        self::assertInstanceOf(SerializerInterface::class, $config->getSerializer());
    }

    public function testDefaultInstance(): void
    {
        $config = new Config();

        self::assertEquals($config, Config::getDefault());
    }

    public function testDefaultRedeliveryTimeInSeconds(): void
    {
        self::assertEquals(180, Config::getDefault()->getRedeliveryTimeInSeconds());
    }

    public function testDefaultNumberOfAttemptsBeforeFailure(): void
    {
        self::assertEquals(5, Config::getDefault()->getNumberOfAttemptsBeforeFailure());
    }

    public function testDefaultGetJobs(): void
    {
        self::assertEquals([], Config::getDefault()->getJobs());
    }

    public function testSeveralGetJobs(): void
    {
        $job1 = new class extends Job {
            public function handle(Message $message, Producer $producer): string
            {
                return Consumer::STATUS_ACK;
            }
        };

        $job2 = new class extends Job {
            public function handle(Message $message, Producer $producer): string
            {
                return Consumer::STATUS_ACK;
            }
        };

        $config = Config::getDefault();
        $config->registerJob('myJob1', $job1);
        $config->registerJob('myJob2', $job2);

        self::assertEquals(['myJob1' => $job1, 'myJob2' => $job2], $config->getJobs());
    }

    public function testDefaultSerializer(): void
    {
        self::assertInstanceOf(BaseSerializer::class, Config::getDefault()->getSerializer());
    }

    public function testGetNotRegistrationJob(): void
    {
        $this->expectException(QueueException::class);
        $this->expectExceptionMessage(sprintf('Job "%s" not registered.', 'not-exists'));

        Config::getDefault()->getJob('not-exists');
    }

    public function testHasNotRegistrationJob(): void
    {
        self::assertFalse(Config::getDefault()->hasJob('not-exists'));
    }

    public function testRegistrationJob(): void
    {
        $job = new class extends Job {
            public function handle(Message $message, Producer $producer): string
            {
                return Consumer::STATUS_ACK;
            }
        };

        $config = Config::getDefault();
        $config->registerJob('myJob', $job);
        $config->registerJob(get_class($job), $job);

        self::assertEquals($job, $config->getJob('myJob'));
        self::assertEquals($job, $config->getJob(get_class($job)));
    }

    public function testRegistrationExistJob(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(sprintf('Job "%s" is already registered.', 'myJob'));

        $job = new class extends Job {
            public function handle(Message $message, Producer $producer): string
            {
                return Consumer::STATUS_ACK;
            }
        };

        $config = Config::getDefault();
        $config->registerJob('myJob', $job);
        $config->registerJob('myJob', $job);
    }

    public function testRegistrationJobWithIncorrectAlias(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(sprintf('Job alias "%s" contains invalid characters.', '!@#$%^&*()_+'));

        $job = new class extends Job {
            public function handle(Message $message, Producer $producer): string
            {
                return Consumer::STATUS_ACK;
            }
        };

        $config = Config::getDefault();
        $config->registerJob('!@#$%^&*()_+', $job);
    }

    public function testGetAliasInRegistrationJob(): void
    {
        $job = new class extends Job {
            public function handle(Message $message, Producer $producer): string
            {
                return Consumer::STATUS_ACK;
            }
        };

        $config = Config::getDefault();
        $config->registerJob('alias', $job);

        self::assertEquals('alias', $config->getJobAlias('alias'));
        self::assertEquals('alias', $config->getJobAlias(get_class($job)));
    }

    public function testGetIncorrectAliasInRegistrationJob(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(sprintf('Job "%s" not registered.', 'non-alias'));

        Config::getDefault()->getJobAlias('non-alias');
    }

    public function testRegistrationProcessor(): void
    {
        $config = Config::getDefault();

        $config->registerProcessor('my_queue', static function (): void {
        });

        self::assertTrue($config->hasProcessor('my_queue'));
    }

    public function testRegistrationExistProcessor(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(sprintf('Processor "%s" is already registered.', 'my_queue'));

        $config = Config::getDefault();

        $config->registerProcessor('my_queue', static function (): void {
        });

        $config->registerProcessor('my_queue', static function (): void {
        });
    }

    public function testGetExistentProcessor(): void
    {
        $config = Config::getDefault();

        $config->registerProcessor('my_queue', static function (): void {
        });

        self::assertIsCallable($config->getProcessor('my_queue'));
    }

    public function testGetNonExistentProcessor(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage(sprintf('Processor "%s" not registered.', 'my_queue'));

        self::assertIsCallable(Config::getDefault()->getProcessor('my_queue'));
    }

    public function testHasExistentProcessor(): void
    {
        $config = Config::getDefault();

        $config->registerProcessor('my_queue', static function (): void {
        });

        self::assertTrue($config->hasProcessor('my_queue'));
    }

    public function testHasNonExistentProcessor(): void
    {
        self::assertFalse(Config::getDefault()->hasProcessor('my_queue'));
    }

    public function testDefaultGetProcessors(): void
    {
        self::assertEquals([], Config::getDefault()->getProcessors());
    }

    public function testSeveralGetProcessors(): void
    {
        $config = Config::getDefault();

        $config->registerProcessor('my_queue1', static function (): void {
        });

        $config->registerProcessor('my_queue2', static function (): void {
        });

        self::assertEquals(['my_queue1', 'my_queue2'], array_keys($config->getProcessors()));
    }
}
