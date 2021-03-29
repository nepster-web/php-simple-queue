PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* **[Store](./store.md)**
* [Configuration](./configuration.md)
* [Producer (Send message)](./producer.md)
* [Consuming](./consuming.md)
* [Example](./example.md)
* [Cookbook](./cookbook.md)

<br>

## :page_facing_up: Store

It is necessary to organize sending and receiving messages from the store.
Currently only supported [Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/) connection.

<br>

**Create connection:**

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

**Create store:**

```php
$store = new \Simple\Queue\Store\DoctrineDbalStore($connection);
```