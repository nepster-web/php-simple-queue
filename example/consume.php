<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';


$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db',
]);

$store = new \Simple\Queue\Store\DoctrineDbalStore($connection);

$producer = new \Simple\Queue\Producer($store);
$consumer = new \Simple\Queue\Consumer($store, $producer);

// create table for queue messages
$store->init();


echo 'Start consuming' . PHP_EOL;

while (true) {

    if ($message = $store->fetchMessage(['my_queue'])) {

        // Your message handling logic

        $consumer->acknowledge($message);

        echo sprintf('Received message: %s ', $message->getBody());
        echo PHP_EOL;
    }

}
