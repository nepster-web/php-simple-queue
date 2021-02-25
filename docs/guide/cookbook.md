PHP Simple Queue Cookbook
=========================

Tips, recommendations and best practices for use this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Usage basics](./usage.md)
* [Example](./example.md)
* [Run consume](./run-consume.md)
* **[Cookbook](./cookbook.md)**


## :page_facing_up: Cookbook

- If you are using docker, run consumer in an individual container. This will allow you to get away from blocking handling and speed up the application.

- If you are using basic example from [consume.php](../../example/consume.php) you need to watch closely behind leaks in php process. [PHP is meant to die](https://software-gunslinger.tumblr.com/post/47131406821/php-is-meant-to-die).

- You can work with serialized objects, but it's better to avoid it. 
    - First you load the queue with big data.
    - Secondly if you change the objects of your application, there is a risk of getting errors when processing messages (which were in the queue).
    - *However, this is a recommendation, not a hard and fast prescription, use common sense.*
