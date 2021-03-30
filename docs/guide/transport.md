PHP Simple Queue Usage basics
=============================

Transport - provides methods for management messages in queue.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* **[Transport](./transport.md)**
* [Configuration](./configuration.md)
* [Producer (Send message)](./producer.md)
* [Consuming](./consuming.md)
* [Example](./example.md)
* [Cookbook](./cookbook.md)

<br>

## :page_facing_up: Transport

The transport uses [Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/) library and SQL 
like server as a broker. It creates a table there. Pushes and pops messages to\from that table.


> Currently only supported Doctrine DBAL.

<br>

**Create connection:**

You can get a DBAL Connection through the Doctrine\DBAL\DriverManager class.

```php
$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'dbname' => 'my_db',
    'user' => 'root',
    'password' => '*******',
    'host' => 'localhost',
    'port' => '5432',
    'driver' => 'pdo_pgsql',
]);
```

or

```php
$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db'
]);
```

[See more information.](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html)

<br>

**Create transport:**

```php
$transport = new \Simple\Queue\Transport\DoctrineDbalTransport($connection);
```

