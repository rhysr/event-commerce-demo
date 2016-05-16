<?php

declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('ecomm_mq', 5672, 'guest', 'guest');
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
    $data = ['orderId' => mt_rand(1, 999999)];
    $msg = new AMQPMessage(json_encode($data));

    $channel->basic_publish($msg, 'orders');
    echo "generated order";
    sleep(5);
}

$channel->close();
$connection->close();
