PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Send message](./send_message.md)
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



## Run in background process

A consumer can be run in the background in several ways:

- [using php cli](#Using-php-cli)
- [run the application in a daemonized docker container](#Run-the-application-in-a-daemonized-docker-container)
- [using supervisor](#Using-supervisor)



### Using php cli
Configure your consume.php and run the command

```bash
exec php /path/to/folder/example/consume.php > /dev/null &
```
the result of a successful launch of the command will be the process code, for example:

```bash
[1] 97285
```

use this to get detailed information about the process.
```bash
ps -l 97285
```


### Run the application in a daemonized docker container

This command will allow your docker container to run in the background:

```bash
docker exec -t -d you-container-name sh -c "php ./path/to/consume.php"
```


#### Using supervisor

Сonfigure your supervisor config file `/etc/supervisor/conf.d/consume.conf`
```bash
[program:consume]
command=/usr/bin/php /path/to/folder/example/consume.php -DFOREGROUND
directory=/path/to/folder/example/
autostart=true
autorestart=true
startretries=5
user=root
numprocs=1
startsecs=0
process_name=%(program_name)s_%(process_num)02d
stderr_logfile=/path/to/folder/example/%(program_name)s_stderr.log
stderr_logfile_maxbytes=10MB
stdout_logfile=/path/to/folder/example/%(program_name)s_stdout.log
stdout_logfile_maxbytes=10MB
```

Let supervisor read our config file `/etc/supervisor/conf.d/consume.conf` to start our service/script.

```bash
$ sudo supervisorctl reread
consume: available
```

Let supervisor start our service/script `/path/to/folder/example/consume.php` based on the config we prepared above.
This will automatically create log files `/path/to/folder/example/consume_stderr.log` and
`/path/to/folder/example/consume_stdout.log`.

```bash
$ sudo supervisorctl update
consume: added process group
```

Lets check if the process is running.

```bash
$ ps aux | grep consume
root  17443  0.0  0.4 194836  9844 ?        S    19:41   0:00 /usr/bin/php /path/to/folder/example/consume.php
```