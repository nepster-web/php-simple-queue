PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Configuration](./configuration.md)
* **[Producer (Send message)](./producer.md)**
* [Consuming](./consuming.md)
* [Example](./example.md)
* [Cookbook](./cookbook.md)

<br>

## :page_facing_up: Producer

You need to configure `$store` and `$config` to send new messages.
[Detailed information](./configuration.md).


**Send a message to queue:**
-------------------------------

```php
$producer = new \Simple\Queue\Producer($store, $config);

$message = (new \Simple\Queue\Message('my_queue', 'my_data'))
    ->setEvent('my_event')
    ->changePriority(\Simple\Queue\Priority::VERY_HIGH);

$producer->send($message);
```

or a simpler example:

```php
$producer = new \Simple\Queue\Producer($store, $config);

$producer->send($producer->createMessage('my_queue', ['my_data']));
```

You can send a message from anywhere in the application to process it in the background. 

<br>

**Send a message to queue through job:**
-------------------------------

```php
$producer = new \Simple\Queue\Producer($store, $config);

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