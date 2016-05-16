<?php

declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('ecomm_mq', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('events', 'fanout', false, false, false);
list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
$channel->queue_bind($queue_name, 'events');

$callback = function ($msg) use ($channel) {
    $incoming = json_decode($msg->body);
    if (!isset($incoming['event'])) {
        return;
    }
    if (!isset($incoming['orderId'])) {
        return;
    }

    echo 'Received ' . $incoming['event'] . " event\n";

    foreach (mt_rand(1, 5) as $idx) {
        $data = [
            'event'   => 'PoCreated',
            'orderId' => $incoming['orderId'],
            'poId'    => mt_rand(1, 999999)
        ];
        $msg = new AMQPMessage(json_encode($data));
        $channel->basic_publish($msg, 'events');
    }
};
$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
        $channel->wait();
}

$channel->close();
$connection->close();
