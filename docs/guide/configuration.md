PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* **[Configuration](./configuration.md)**
* [Producer (Send message)](./producer.md)
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