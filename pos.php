<?php

declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Ramsey\Uuid\Uuid;

$connection = new AMQPStreamConnection('mq', 5672, 'guest', 'guest');
$channel    = $connection->channel();

$exchangeName = 'events';
$channel->exchange_declare($exchangeName, 'topic', false, false, false);

// bind one callback to each topic that we care about

// Order creation topic
$orderCreationCallback = function ($msg) use ($channel, $exchangeName) {
    $order = json_decode($msg->body, true);
    if (!isset($order['event'])) {
        return;
    }
    if (!isset($order['orderId'])) {
        return;
    }

    $unassigned      = $order['lines'];
    $lineCount       = count($order['lines']);

    do {
        $assignLinesCount = mt_rand(1, count($unassigned));
        $linesToAssign   = array_splice($unassigned, 0, $assignLinesCount);

        $po = [
            'event'   => 'PoCreated',
            'orderId' => $order['orderId'],
            'poId'    => Uuid::uuid1()->toString(),
            'lines'   => [],
        ];
        foreach ($linesToAssign as $line) {
            $po['lines'][] = array_pop($linesToAssign);
        }

        echo "Creating PO ${po['poId']} for order ${order['orderId']} with ${assignLinesCount} of ${lineCount} products\n";

        $msg = new AMQPMessage(
            json_encode($po),
            [
                'content_type' => 'application/json',
            ]
        );
        $channel->basic_publish($msg, $exchangeName, 'po.created');

        $unassignedCount = count($unassigned);
    } while ($unassignedCount > 0);
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
