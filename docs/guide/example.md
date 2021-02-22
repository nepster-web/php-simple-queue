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