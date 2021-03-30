PHP Simple Queue Usage basics
=============================

Message producer object to send messages to a queue.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Transport](./transport.md)
* [Configuration](./configuration.md)
* **[Producer (Send message)](./producer.md)**
* [Consuming](./consuming.md)
* [Example](./example.md)
* [Cookbook](./cookbook.md)

<br>

## :page_facing_up: Producer

You need to configure [$transport](./transport.md) and [$config](./configuration.md) to send new messages.

<br>

**Send a new message to queue:**
-------------------------------

```php
$producer = new \Simple\Queue\Producer($transport, $config);

$producer->send($producer->createMessage('my_queue', ['my_data']));
```

or a custom example (you need to think about serialization):

```php
$producer = new \Simple\Queue\Producer($transport, $config);

$message = (new \Simple\Queue\Message('my_queue', 'my_data'))
    ->withEvent('my_event')
    ->changePriority(\Simple\Queue\Priority::VERY_HIGH);

$producer->send($message);
```

You can send a message from anywhere in the application to process it in the background. 

<br>

**Send a new message to queue through job:**
-------------------------------

```php
$producer = new \Simple\Queue\Producer($transport, $config);

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
$message->changeRedeliveredAt($redeliveredAt);
$message->changeQueue($queue);
$message->changePriority($priority);
$message->withEvent($event);
```

Each message has [Status](../../src/Status.php) and [Priority](../../src/Priority.php).

* **Status** <br>
  Used to delimit messages in a queue (system parameter, not available for public modification). <br>
  Possible options: NEW; IN_PROCESS; ERROR; REDELIVERED.


* **Priority** <br>
  Used to sort messages in the consumer. <br>
  Possible options: VERY_LOW = -2; LOW = -1; DEFAULT = 0; HIGH = 1; VERY_HIGH = 2.