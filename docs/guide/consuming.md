PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Usage basics](./usage.md)
* **[Consuming](./consuming.md)**
* [Example](./example.md)
* [Cookbook](./cookbook.md)


## Consuming

You need to configure [Consumer.php](./../../src/Consumer.php) to read and processing messages from the queue.

You can use a simple php cli, [Symfony/Console](https://symfony.com/doc/current/components/console.html)
or any other component, it really doesn't matter.
The main idea is to run Consumer in a separate process in the background.


Use your imagination to handling your messages.



**Simple example for consuming with processors:**
-------------------------------

Processor is responsible for processing consumed messages.

```php
<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';


$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db'
]);

$tableCreator = new \Simple\Queue\QueueTableCreator($connection);

$producer = new \Simple\Queue\Producer($connection);
$consumer = new \Simple\Queue\Consumer($connection, $producer);

// create table for queue messages
$tableCreator->createDataBaseTable();


echo 'Start consuming' . PHP_EOL;

// register process for processing all messages for "my_queue"
$consumer->bind('my_queue', static function(\Simple\Queue\Message $message, \Simple\Queue\Producer $producer): string {

    // Your message handling logic
    var_dump($message->getBody() . PHP_EOL);

    return \Simple\Queue\Consumer::STATUS_ACK;
});

$consumer->consume();
```



**Simple example for consuming with jobs:**
-------------------------------








**Custom example for consuming:**
-------------------------------

You can configure message handling yourself.

```php
<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';


$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db'
]);

$tableCreator = new \Simple\Queue\QueueTableCreator($connection);

$producer = new \Simple\Queue\Producer($connection);
$consumer = new \Simple\Queue\Consumer($connection, $producer);

// create table for queue messages
$tableCreator->createDataBaseTable();


echo 'Start consuming' . PHP_EOL;

while (true) {

    if ($message = $consumer->fetchMessage(['my_queue'])) {

        // Your message handling logic

        $consumer->acknowledge($message);

        echo sprintf('Received message: %s ', $message->getBody());
        echo PHP_EOL;
    }

}
```


## Message processing statuses

if you use jobs or processors when processing a message, you must return the appropriate status:

* **ACK** - `\Simple\Queue\Consumer::STATUS_ACK` - message has been successfully processed and will be removed from the queue.


* **REJECT** - `\Simple\Queue\Consumer::STATUS_REJECT` - message has not been processed but is no longer required.


* **REQUEUE** - `\Simple\Queue\Consumer::STATUS_REQUEUE` - message has not been processed, it is necessary redelivered.