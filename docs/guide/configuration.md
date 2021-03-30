PHP Simple Queue Usage basics
=============================

Configuration.


## :book: Guide

* [Guide](./README.md)
* [Install](./install.md)
* [Transport](./transport.md)
* **[Configuration](./configuration.md)**
* [Producer (Send message)](./producer.md)
* [Consuming](./consuming.md)
* [Example](./example.md)
* [Cookbook](./cookbook.md)

<br>

## :page_facing_up: Configuration

You need to use the same config for producer and consumer.

<br>

**Create example config:**

```php
$config = \Simple\Queue\Config::getDefault()
    ->changeRedeliveryTimeInSeconds(100)
    ->changeNumberOfAttemptsBeforeFailure(3)
    ->withSerializer(new \Simple\Queue\Serializer\BaseSerializer())
    ->registerJob(MyJob::class, new MyJob())
    ->registerProcessor('my_queue', static function(\Simple\Queue\Message $message, \Simple\Queue\Producer $producer): string {
   
        // Your message handling logic
        
        return \Simple\Queue\Consumer::STATUS_ACK;
    });
```


