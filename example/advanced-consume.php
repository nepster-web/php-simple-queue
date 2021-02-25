<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';


$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db'
]);

$producer = new \Simple\Queue\Producer($connection);
$consumer = new \Simple\Queue\Consumer($connection, $producer);


echo 'Start consuming' . PHP_EOL;


// process all messages from queue
$consumer->bind('my_queue', static function(\Simple\Queue\Message $message, \Simple\Queue\Producer $producer): string {

    // Your message handling logic

    return 'status';
});

// process all messages from queue with event
$consumer->bind('my_queue.my_event', static function(\Simple\Queue\Message $message, \Simple\Queue\Producer $producer): string {

    // Your message handling logic

    return 'status';
});

$consumer->consume();
