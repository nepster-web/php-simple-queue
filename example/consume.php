<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db',
]);

$transport = new \Simple\Queue\Transport\DoctrineDbalTransport($connection);

$producer = new \Simple\Queue\Producer($transport);
$consumer = new \Simple\Queue\Consumer($transport, $producer);

// create table for queue messages
$transport->init();


echo 'Start consuming' . PHP_EOL;

while (true) {

    if ($message = $transport->fetchMessage(['my_queue'])) {

        // Your message handling logic

        $consumer->acknowledge($message);

        echo sprintf('Received message: %s ', $message->getBody());

        echo PHP_EOL;
    }

}
