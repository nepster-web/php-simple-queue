PHP Simple Queue Cookbook
=========================

Tips, recommendations and best practices for use this library.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Configuration](./configuration.md)
* [Producer (Send message)](./producer.md)
* [Consuming](./consuming.md)
* [Example](./example.md)
* **[Cookbook](./cookbook.md)**

<br>


## :page_facing_up: Cookbook

- If you are using docker, run consumer in an individual container. This will allow you to get away from blocking handling and speed up the application.

- You can work with serialized objects, but it's better to avoid it. 
    - First you load the queue with big data.
    - Secondly if you change the objects of your application, there is a risk of getting errors when processing messages (which were in the queue).
    - *However, this is a recommendation, not a hard and fast prescription, use common sense.*
    
- If you are using jobs we recommend using job aliases instead of class namespace. Because in the case of a refactor class or namespace may change messages from the queue continue to be processed.

- If you are using basic example from [consume.php](../../example/consume.php) you need to watch closely behind leaks in php process. [PHP is meant to die](https://software-gunslinger.tumblr.com/post/47131406821/php-is-meant-to-die).