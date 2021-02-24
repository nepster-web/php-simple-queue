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
