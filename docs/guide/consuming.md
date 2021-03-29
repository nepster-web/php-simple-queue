PHP Simple Queue Usage basics
=============================

An example of using this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Store](./store.md)
* [Configuration](./configuration.md)
* [Producer (Send message)](./producer.md)
* **[Consuming](./consuming.md)**
* [Example](./example.md)
* [Cookbook](./cookbook.md)

<br>

## Consuming

You need to configure [$store](./store.md) and [$config](./configuration.md) to read and processing messages from the queue.
[Detailed information](./configuration.md).

You can use a simple php cli, [Symfony/Console](https://symfony.com/doc/current/components/console.html)
or any other component, it really doesn't matter.
The main idea is to run Consumer in a separate process in the background.


Use your imagination to handling your messages.


<br>

**Simple example for consuming with processors:**
-------------------------------

Processor is responsible for processing consumed messages.

```php
<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

$producer = new \Simple\Queue\Producer($store, $config);
$consumer = new \Simple\Queue\Consumer($store, $producer, $config);

echo 'Start consuming' . PHP_EOL;

$consumer->consume();
```

<br>

**Job processing:**
-------------------------------

```php
<?php
    //
```


<br>

**Consuming algorithm:**
-------------------------------

```$consumer->consume();``` - base realization of consumer.

If the message table does not exist, it will be created.

Next, will start endless loop ```while(true)``` to get the next message from the queue.
if there are no messages, there will be a sustained seconds pause.

When the message is received, it will be processed. Job has priority over the processor.

If an uncaught error occurs, it will be caught and increment first processing attempt.

After several unsuccessful attempts, the message will status `\Simple\Queue\Status::FAILURE`.

If there are no handlers for the message, the message will status `\Simple\Queue\Status::UNDEFINED_HANDLER`.

> Messages are processed with statuses: `\Simple\Queue\Status::NEW` and `\Simple\Queue\Status::REDELIVERED`

<br>


**Custom example for consuming:**
-------------------------------

You can configure message handling yourself.

```php
<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

$producer = new \Simple\Queue\Producer($store, $config);
$consumer = new \Simple\Queue\Consumer($store, $producer, $config);

// create table for queue messages
$store->init();

echo 'Start consuming' . PHP_EOL;

while (true) {

    if ($message = $store->fetchMessage(['my_queue'])) {

        // Your message handling logic

        $consumer->acknowledge($message);

        echo sprintf('Received message: %s ', $message->getBody());
        echo PHP_EOL;
    }

}
```

<br>

## Message processing statuses

If you use jobs or processors when processing a message, you must return the appropriate status:

* **ACK** - `\Simple\Queue\Consumer::STATUS_ACK` - message has been successfully processed and will be removed from the queue.


* **REJECT** - `\Simple\Queue\Consumer::STATUS_REJECT` - message has not been processed but is no longer required.


* **REQUEUE** - `\Simple\Queue\Consumer::STATUS_REQUEUE` - message has not been processed, it is necessary redelivered.

<br>

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


### Using supervisor

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