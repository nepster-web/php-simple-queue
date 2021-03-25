PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* **[Producer (Send message)](./producer.md)**
* [Consuming](./consuming.md)
* [Example](./example.md)
* [Cookbook](./cookbook.md)

<br>

## :page_facing_up: Usage basics

Let's define the terms:

* **Producer** - to send a message to the queue.
* **Consumer** - handler, built on top of a transport functionality. The goal of the component is to simply consume messages.
* **Message** - data to be processed.


Next you need to configure and run **Consumer** and then send messages.

<br>

**Config:**
-------------------------------

You can use config for producer and consumer:

```php
$config = \Simple\Queue\Config::getDefault()
    ->changeNumberOfAttemptsBeforeFailure(5)
    ->changeRedeliveryTimeInSeconds(180)
    ->withSerializer(new \Simple\Queue\Serializer\SymfonySerializer());
```

<br>

**Create connection:**
-------------------------------

You can get a DBAL Connection through the Doctrine\DBAL\DriverManager class.

```php
$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db'
]);
```

or

```php
$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'dbname' => 'my_db',
    'user' => 'root',
    'password' => '*******',
    'host' => 'localhost',
    'port' => '54320',
    'driver' => 'pdo_pgsql',
]);
```

[See more information.](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html)

<br>

**Send a message to queue:**
-------------------------------

```php
$store = new \Simple\Queue\Store\DoctrineDbalStore($connection);
$producer = new \Simple\Queue\Producer($store);

$message = (new \Simple\Queue\Message('my_queue', 'my_data'))
    ->setEvent('my_event')
    ->changePriority(\Simple\Queue\Priority::VERY_HIGH);

$producer->send($message);
```

or a simpler example:

```php
$store = new \Simple\Queue\Store\DoctrineDbalStore($connection);
$producer = new \Simple\Queue\Producer($store);

$producer->send($producer->createMessage('my_queue', ['my_data']));
```

You can send a message from anywhere in the application to process it in the background. 

<br>

**Send a message to queue through job:**
-------------------------------

```php
$store = new \Simple\Queue\Store\DoctrineDbalStore($connection);
$producer = new \Simple\Queue\Producer($store);

$producer->dispatch(MyJob::class, ['key' => 'value']);
```

<br>

> You can send a message to the queue from anywhere in the application where available $producer.

<br>

**Message**
----------------------

Description of the base entity [Message](../../src/Message.php).

```php

// create new Message
$message = new \Simple\Queue\Message('my_queue', 'my_data');

// public getters
$message->getId();
$message->getStatus();
$message->isJob();
$message->getError();
$message->getExactTime();
$message->getCreatedAt();
$message->getAttempts();
$message->getQueue();
$message->getEvent();
$message->getBody();
$message->getPriority();
$message->getRedeliveredAt();
$message->isRedelivered();

// public setters
$message->setRedeliveredAt($redeliveredAt);
$message->changeQueue($queue);
$message->changePriority($priority);
$message->setEvent($event);
```

Each message has [Status](../../src/Status.php) and [Priority](../../src/Priority.php).

* **Status** <br>
  Used to delimit messages in a queue (system parameter, not available for public modification). <br>
  Possible options: NEW; IN_PROCESS; ERROR; REDELIVERED.


* **Priority** <br>
  Used to sort messages in the consumer. <br>
  Possible options: VERY_LOW = -2; LOW = -1; DEFAULT = 0; HIGH = 1; VERY_HIGH = 2.