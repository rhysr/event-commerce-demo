<?php

declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('mq', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('orders', 'fanout', false, false, false);

$exit = false;
// signal handler function
$signalHandler = function ($signo) use (&$exit) {
    echo "Signal received...shutting down...\n";
    $exit = true;
};

pcntl_signal(SIGTERM, $signalHandler);

while (!$exit) {
    $data = [
        'event' => 'OrderCreated',
        'orderId' => mt_rand(1, 999999),
        'lines' => [],
    ];

    foreach (range(1, mt_rand(1, 8)) as $lineIdx) {
        $data['lines'][] = [
            'productId' => mt_rand(1, 999999),
            'quantity' => mt_rand(1, 20),
        ];
    }
    $msg = new AMQPMessage(json_encode($data));

    $channel->basic_publish($msg, 'orders');
    echo "Generated order ${data['orderId']}\n";
    sleep(5);
}

$channel->close();
$connection->close();
