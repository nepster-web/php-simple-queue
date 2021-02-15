<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

$connection = ''; // TODO: need Doctrine\DBAL\Connection

$tableCreator = new \Simple\Queue\QueueTableCreator($connection);

$producer = new \Simple\Queue\Producer($connection);
$consumer = new \Simple\Queue\Consumer($connection, $producer);

// create table for queue messages
$tableCreator->createDataBaseTable();


echo 'Start consuming' . PHP_EOL;

while (true) {

    if ($message = $consumer->fetchMessage('my_queue')) {

        // Your message handling logic

        $consumer->acknowledge($message);

        echo sprintf('Received message: %s ', $message->getBody());
        echo PHP_EOL;
    }

}
