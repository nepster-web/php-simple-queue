PHP Simple Queue Example
========================

Demonstration of work PHP Simple Queue using SQLite.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Usage basics](./usage.md)
* **[Example](./example.md)**
* [Cookbook](./cookbook.md)


## :page_facing_up: Example

Example of sending messages to a queue: [produce.php](../../example/produce.php)

Example of processing messages from a queue: [consume.php](../../example/consume.php)


## Quick run with docker

`cd .docker` - go to dir.


**Run the following commands:**

- `make build` - build docker container
- `make start` - start docker container
- `make php cmd='./example/consume.php'` - run consume (to read messages from the queue)
- `make php cmd='./example/produce.php'` - run produce (to sent messages to the queue)


> Both examples should work in different tabs because they are daemons (while(true){}).


**Also, you can access to php:**

- `make php cmd='-v'`:
- `make composer cmd='update'`:


## Run in background process

A consumer can be run in the background in several ways:

- [using php cli](#Using-php-cli)
- [run the application in a daemonized docker container](#Run-the-application-in-a-daemonized-docker-container)
- [using supervisor](#Using-supervisor)


### Using php cli
Execute the command as in the example below

> NOTE: in order for the consumer example to start correctly, you must have a locally installed database and set up 
> a connection to it. Otherwise, you will receive a connection error.

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

You can take our example as a basis for the docker assembly and execute the following command:
> NOTE: you must first start the docker container. [see](#Quick-run-with-docker)

```bash
docker exec -t -d php-simple-queue sh -c "php ./example/consume.php"
```

#### Using supervisor

Create config
```bash
$ sudo nano /etc/supervisor/conf.d/consume.conf

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
$ ps aux | grep hello-world
root  17443  0.0  0.4 194836  9844 ?        S    19:41   0:00 /usr/bin/php /path/to/folder/example/consume.php
```
