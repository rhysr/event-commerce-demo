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

// PO creation topic
$poCreationCallback = function ($msg) use ($channel, $exchangeName) {
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
    if (!isset($incoming['lines'])) {
        return;
    }

    foreach ($incoming['lines'] as $line) {
        $stock = [
            'productId' => $line['productId'],
            'stockLevel' => mt_rand(0, 999),
            'adjustment' => 0 - $line['quantity'],
        ];
        $initialStockLevel = $stock['stockLevel'] - $stock['adjustment'];
        echo "Adjust stock level for product: ${stock['productId']} from: ${initialStockLevel} to: ${stock['stockLevel']}\n";
        $msg = new AMQPMessage(json_encode($stock));
        $channel->basic_publish($msg, $exchangeName, 'product.stock');
    }

};

$poCreateQueue = 'po.created.product.stock';
$channel->queue_declare($poCreateQueue, false, false, true, false);
$channel->queue_bind($poCreateQueue, $exchangeName, 'po.created');
$channel->basic_consume($poCreateQueue, '', false, true, false, false, $poCreationCallback);


while (count($channel->callbacks)) {
        $channel->wait();
}

$channel->close();
$connection->close();
