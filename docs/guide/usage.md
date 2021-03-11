PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* **[Usage basics](./usage.md)**
* [Consuming](./consuming.md)
* [Example](./example.md)
* [Cookbook](./cookbook.md)


## :page_facing_up: Usage basics

Let's define the terms:

* **Producer** - to send a message to the queue.
* **Consumer** - handler, built on top of a transport functionality. The goal of the component is to simply consume messages.
* **Message** - data to be processed.

<br>

Next you need to configure and run **Consumer** and then send messages.

<br>


**Example of message sending:**
-------------------------------

```php
$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db'
]);

$producer = new \Simple\Queue\Producer($connection);

$message = (new \Simple\Queue\Message('my_queue', 'my_data'))
    ->setEvent('my_event')
    ->changePriority(new Priority(Priority::VERY_HIGH));

$producer->send($message);
```

You can send a message from anywhere in the application to process it in the background. 

<br>

**Config:**
-------------------------------

```php
 // 
```


<br>


**Example of message sending through job:**
-------------------------------

```php
 //
```

<br>

**Message**
----------------------

Description of the base entity [Message](../../src/Message.php)

```php

// create new Message
$message = new Message('my_queue', 'my_data');

// public getters
$message->getId();
$message->getStatus();
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