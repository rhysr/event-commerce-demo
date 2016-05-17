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

// Order creation topic
$orderCreationCallback = function ($msg) use ($channel, $exchangeName) {
    $incoming = json_decode($msg->body, true);
    if (!isset($incoming['event'])) {
        return;
    }
    if (!isset($incoming['orderId'])) {
        return;
    }

    echo "Order ${incoming['orderId']} was created\n";

    // one product per PO for demo
    foreach ($incoming['lines'] as $line) {
        $data = [
            'event'   => 'PoCreated',
            'orderId' => $incoming['orderId'],
            'poId'    => mt_rand(1, 999999),
            'lines'   => [
                $line,
            ],
        ];
        echo "Creating PO ${data['poId']} for order ${incoming['orderId']}\n";
        $msg = new AMQPMessage(json_encode($data));
        $channel->basic_publish($msg, $exchangeName, 'po.created');
    }
};

$orderCreateQueue = 'order.created.po.create';
$channel->queue_declare($orderCreateQueue, false, false, true, false);
$channel->queue_bind($orderCreateQueue, $exchangeName, 'order.created');
$channel->basic_consume($orderCreateQueue, '', false, true, false, false, $orderCreationCallback);


while (count($channel->callbacks)) {
        $channel->wait();
}

$channel->close();
$connection->close();
