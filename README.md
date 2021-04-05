> This package is under development. Api classes of this application can be changed.

<p align="center">
    <h1 align="center">PHP Simple Queue</h1>
</p>

<p align="center">
    <a href="https://packagist.org/packages/nepster-web/php-simple-queue"><img src="https://shields.io/packagist/v/nepster-web/php-simple-queue.svg?include_prereleases" alt="Release"></a>
    <a href="https://travis-ci.org/github/nepster-web/php-simple-queue"><img src="https://travis-ci.org/nepster-web/php-simple-queue.svg?branch=master" alt="Build"></a>
    <a href="https://scrutinizer-ci.com/g/nepster-web/php-simple-queue/?b=master"><img src="https://scrutinizer-ci.com/g/nepster-web/php-simple-queue/badges/coverage.png?b=master" alt="Coverage"></a>
    <a href="https://packagist.org/packages/nepster-web/php-simple-queue"><img src="https://img.shields.io/packagist/dt/nepster-web/php-simple-queue.svg" alt="Downloads"></a>
    <a href="https://packagist.org/packages/nepster-web/php-simple-queue"><img src="https://img.shields.io/packagist/l/nepster-web/php-simple-queue" alt="License"></a>
</p>

<p align="center">
    <img src="./docs/php-simple-queue-941x320.gif" width="941" alt="Example of work" />
</p>


Introduction
------------

**PHP Simple Queue** - a library for running tasks asynchronously via queues.
It is production ready, battle-tested a simple messaging solution for PHP.

It supports queues based on **DB**.

Requirements
------------

You'll need at least PHP 7.4 (it works best with PHP 8).


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/):

Either run

```
php composer.phar require --prefer-dist nepster-web/php-simple-queue
```

or add

```
"nepster-web/php-simple-queue": "*"
```


:computer: Basic Usage
----------------------

Create transport ([see more information](./docs/guide/transport.md)):

```php
$transport = new \Simple\Queue\Transport\DoctrineDbalTransport($connection);
```


### Send a new message to queue (producing)

```php
$config = \Simple\Queue\Config::getDefault()
    ->registerProcessor('my_queue', static function(\Simple\Queue\Context $context): string {
        // Your message handling logic
        return \Simple\Queue\Consumer::STATUS_ACK;
    });
    
$producer = new \Simple\Queue\Producer($transport, $config);

$message = $producer->createMessage('my_queue', ['key' => 'value']);

$producer->send($message);
```


### Job dispatching (producing)

```php
$config = \Simple\Queue\Config::getDefault()
    ->registerJob(MyJob::class, new MyJob());

$producer = new \Simple\Queue\Producer($transport, $config);

$producer->dispatch(MyJob::class, ['key' => 'value']);
```


### Processing messages from queue (consuming)

```php
$producer = new \Simple\Queue\Producer($transport, $config);
$consumer = new \Simple\Queue\Consumer($transport, $producer, $config);

$consumer->consume();
```

For more details see the [example code](./example) and read the [guide](./docs/guide/example.md).


### Testing

To run the tests locally, in the root directory execute below

```
./vendor/bin/phpunit
```

or you can run tests in a docker container

```
cd .docker
make build
make start 
make composer cmd='test'
```

---------------------------------

## :book: Documentation

See [the official guide](./docs/guide/README.md).


## :books: Resources

* [Documentation](./docs/guide/README.md)
* [Example](./example)
* [Issue Tracker](https://github.com/nepster-web/php-simple-queue/issues)


## :newspaper: Changelog

Detailed changes for each release are documented in the [CHANGELOG.md](./CHANGELOG.md).


## :lock: License

See the [MIT License](LICENSE) file for license rights and limitations (MIT).