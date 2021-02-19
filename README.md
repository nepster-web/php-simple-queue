<p align="center">
    <h1 align="center">PHP Simple Queue</h1>
</p>


Introduction
------------

**PHP Simple Queue** - a library for running tasks asynchronously via queues.
It is production ready, battle-tested a simple messaging solution for PHP.

It supports queues based on **DB**.

Requirements
------------

You'll need at least PHP 7.4 (it works best with PHP 8).


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/):

Either run

```
php composer.phar require --prefer-dist nepster-web/simple-queue
```

or add

```
"nepster-web/simple-queue": "*"
```


:computer: Basic Usage
----------------------

### Send to queue (producing)

```
$message = new Message('my_queue', json_decode($data));
$producer->send($message);
```

### Read from queue (consuming)

```
while (true) {
    if ($message = $consumer->fetchMessage('my_queue')) {
        // Your message handling logic
        $consumer->acknowledge($message);
    }
}
```


### Testing

To run the tests, in the root directory execute below.

```
./vendor/bin/phpunit
```


---------------------------------


## :book: Documentation

See [the official guide](./docs/guide/README.md).


## :books: Resources

* [Documentation](./docs/guide/README.md)
* [Example](./example)
* [Issue Tracker](https://github.com/nepster-web/simple-queue/issues)


## :newspaper: Changelog

Detailed changes for each release are documented in the [CHANGELOG.md](./CHANGELOG.md).


## :lock: License

See the [MIT License](LICENSE) file for license rights and limitations (MIT).