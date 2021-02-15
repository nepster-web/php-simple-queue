<?php

declare(strict_types=1);

include __DIR__ . '/../vendor/autoload.php';

$connection = ''; // TODO: need Doctrine\DBAL\Connection

$producer = new \Simple\Queue\Producer($connection);

//
while (true) {

    $message = (new \Simple\Queue\Message('test', uniqid('', true)))
        ->setEvent('create_order');

    $producer->send($message);

    echo sprintf('Sent message: %s ', $message->getBody());
    echo PHP_EOL;

    sleep(1);

}