<?php

declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('mq', 5672, 'guest', 'guest');
$channel    = $connection->channel();

$exchangeName = 'events';
$channel->exchange_declare($exchangeName, 'topic', false, false, false);

// bind one callback to each topic that we care about

// Order placed topic
$orderPlacedCallback = function ($msg) use ($channel, $exchangeName) {
    $placement = json_decode($msg->body, true);
    if (!isset($placement['orderId'])) {
        return;
    }

    echo "Order ${placement['orderId']} received and created\n";

    $msg = new AMQPMessage(
        json_encode($placement),
        [
            'content_type' => 'application/json',
        ]
    );
    $channel->basic_publish($msg, $exchangeName, 'order.created');
};

$orderPlacedQueue = 'order.placed.order.create';
$channel->queue_declare($orderPlacedQueue, false, false, true, false);
$channel->queue_bind($orderPlacedQueue, $exchangeName, 'order.placed');
$channel->basic_consume($orderPlacedQueue, '', false, true, false, false, $orderPlacedCallback);


while (count($channel->callbacks)) {
        $channel->wait();
}

$channel->close();
$connection->close();
