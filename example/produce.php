<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

$connection = \Doctrine\DBAL\DriverManager::getConnection([
    'driver' => 'pdo_sqlite',
    'path' => '/db/queue.db'
]);

$producer = new \Simple\Queue\Producer($connection, null);


echo 'Start send to queue' . PHP_EOL;

while (true) {

    $message = new \Simple\Queue\Message('my_queue', uniqid('', true));

    $producer->send($message);

    echo sprintf('Sent message: %s ', $message->getBody());
    echo PHP_EOL;

    sleep(1);

}
