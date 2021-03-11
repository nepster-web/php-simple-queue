<?php

declare(strict_types=1);

namespace Simple\QueueTest;

use RuntimeException;
use Simple\Queue\Config;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
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
            ->changeRedeliveryTime(250)
            ->changeNumberOfAttemptsBeforeFailure(4)
            ->registerJobAlias('test', 'TestClass')
            ->withSerializer($this->createMock(SerializerInterface::class));

        self::assertEquals(250, $config->getRedeliveryTimeInSeconds());
        self::assertEquals(4, $config->getNumberOfAttemptsBeforeFailure());
        self::assertEquals('TestClass', $config->getJob('test'));
        self::assertTrue($config->hasJob('test'));
        self::assertArrayHasKey('test', $config->getJobs());
        self::assertInstanceOf(SerializerInterface::class, $config->getSerializer());
    }

    public function testRegisterJobWithIncorrectName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The job alias "%Tes*" contains invalid characters.');

        (new Config())->registerJobAlias('%Tes*', 'TestJob');
    }

    public function testRegisterExistsJob(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job "test" is already registered in the jobs.');

        (new Config())
            ->registerJobAlias('test', 'TestJob')
            ->registerJobAlias('test', 'TestJob');
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

    public function testDefaultSerializer(): void
    {
        self::assertInstanceOf(BaseSerializer::class, Config::getDefault()->getSerializer());
    }

    public function testGetNotExistsJob(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Job "%s" doesn\'t exists.', 'not-exists'));

        Config::getDefault()->getJob('not-exists');
    }

    public function testHasJob(): void
    {
        $config = Config::getDefault()->registerJobAlias('exists', \stdClass::class);

        self::asserttrue($config->hasJob('exists'));
        self::assertFalse(Config::getDefault()->hasJob('not-exists'));
    }
}
