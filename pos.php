<?php

declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('mq', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('orders', 'fanout', false, false, false);
list($orders_queue_name, ,) = $channel->queue_declare("", false, false, true, false);
$channel->queue_bind($orders_queue_name, 'orders');

$channel->exchange_declare('pos', 'fanout', false, false, false);
list($pos_queue_name, ,) = $channel->queue_declare("", false, false, true, false);
$channel->queue_bind($pos_queue_name, 'pos');

$channel->exchange_declare('products', 'fanout', false, false, false);

// Listen to orders
$orders_callback = function ($msg) use ($channel) {
    $incoming = json_decode($msg->body, true);
    if (!isset($incoming['event'])) {
        return;
    }
    if (!isset($incoming['orderId'])) {
        return;
    }

    if ('OrderCreated' === $incoming['event']) {
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
            $channel->basic_publish($msg, 'pos');
        }
    }
};
$channel->basic_consume($orders_queue_name, '', false, true, false, false, $orders_callback);

$pos_callback = function ($msg) use ($channel) {
    $incoming = json_decode($msg->body, true);
    if (!isset($incoming['event'])) {
        return;
    }
    if (!isset($incoming['orderId'])) {
        return;
    }
    if (!isset($incoming['poId'])) {
        return;
    }

    if ('PoCreated' === $incoming['event']) {
        echo "PO ${incoming['poId']} was created\n";

        // update stock
        foreach ($incoming['lines'] as $line) {
            $data = [
                'event'   => 'ProductStockUpdated',
                'productId' => $line['productId'],
                'stockLevel' => mt_rand(0, 450),
            ];
            echo "Adjust stock levels for ${line['productId']}\n";
            $msg = new AMQPMessage(json_encode($data));
            $channel->basic_publish($msg, 'products');
        }
    }
};
$channel->basic_consume($pos_queue_name, '', false, true, false, false, $pos_callback);

while (count($channel->callbacks)) {
        $channel->wait();
}

$channel->close();
$connection->close();
