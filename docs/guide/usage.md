PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* **[Usage basics](./usage.md)**
* [Example](./example.md)
* [Cookbook](./cookbook.md)


## :page_facing_up: Usage basics


### Terms

**Producer** - to send a message to the queue.

**Consumer** - handler, built on top of a transport functionality. The goal of the component is to simply consume messages.

**Message** - data to be processed


### Simple use case

First you need to configure and run **Consumer**.

**Simple example for consuming:**

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

    if ($message = $consumer->fetchMessage('my_queue')) {

        // Your message handling logic

        $consumer->acknowledge($message);

        echo sprintf('Received message: %s ', $message->getBody());
        echo PHP_EOL;
    }

}
```

You can use a simple php cli, [Symfony/Console](https://symfony.com/doc/current/components/console.html)
or any other component, it really doesn't matter.
The main idea is to run Consumer in a separate process in the background.


Use your imagination to handling your messages.


**Example of message sending:**

```php
$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db'
]);

$producer = new \Simple\Queue\Producer($connection);

$message = (new \Simple\Queue\Message('my_queue', uniqid('', true)))
    ->setEvent('my_event')
    ->changePriority(new Priority(Priority::VERY_HIGH));

$producer->send($message);
```

You can send a message from anywhere in the application to process it in the background. 


### Message processing