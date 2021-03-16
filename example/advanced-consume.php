<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';


$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db',
]);

$producer = new \Simple\Queue\Producer($connection, null);
$consumer = new \Simple\Queue\Consumer($connection, $producer, null);


echo 'Start consuming' . PHP_EOL;

// process all messages from queue
$consumer->bind('my_queue', static function(\Simple\Queue\Message $message, \Simple\Queue\Producer $producer): string {

    // Your message handling logic
    var_dump($message->getBody() . PHP_EOL);

    return \Simple\Queue\Consumer::STATUS_ACK;
});

$consumer->consume();
