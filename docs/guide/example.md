PHP Simple Queue Example
========================

Demonstration of work PHP Simple Queue using SQLite.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Transport](./transport.md)
* [Configuration](./configuration.md)
* [Producer (Send message)](./producer.md)
* [Consuming](./consuming.md)
* **[Example](./example.md)**
* [Cookbook](./cookbook.md)

<br>

## :page_facing_up: Example

Example of sending messages to a queue: [produce.php](../../example/produce.php)

Example of processing messages from a queue: [consume.php](../../example/consume.php)

Advanced example of processing messages from a queue: [advanced-consume.php](../../example/advanced-consume.php)

<br>

## Quick run with docker

`cd .docker` - go to dir.


**Run the following commands:**

- `make build` - build docker container
- `make start` - start docker container
- `make php cmd='./example/consume.php'` - run consume (to read messages from the queue)
- `make php cmd='./example/produce.php'` - run produce (to sent messages to the queue)


> Both examples should work in different tabs because they are daemons (while(true){}).

<br>

**PHP command access:**

- `make php cmd='{you_command}'`:
- Example: `make php cmd='-v'`:

<br>

**Composer command access:**
- `make composer cmd='{you_command}'`:
- Example: - `make composer cmd='update'`: