<p align="center">
    <h1 align="center">PHP Simple Queue</h1>
    <br>
</p>

An extension for running tasks asynchronously via queues.

It supports queues based on **DB**.



- The minimum required PHP version of PHP Simple Queue is PHP 7.2.
- It works best with PHP 8.
- [Follow the Definitive Guide](./docs/guide/README.md)
  in order to get step by step instructions.



Requirements
------------

You'll need at least PHP 7.4.


Introduction
------------

**Enqueue** is production ready, battle-tested messaging solution for PHP. Provides a common way for programs to create, send, read messages.

This is a main development repository. It provides a friendly environment for productive development and testing of all Enqueue related features&packages.


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



Basic Usage
-----------



## Testing

To run the tests, in the root directory execute below.

```
./vendor/bin/phpunit
```


## Resources

* [Site](https://enqueue.forma-pro.com/)
* [Documentation](./docs/guide/README.md)
* [Example](./example)
* [Issue Tracker](https://github.com/nepster-web/simple-queue/issues)


## License

It is released under the [MIT License](LICENSE).