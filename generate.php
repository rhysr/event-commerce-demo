<?php

declare(ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire;
use Ramsey\Uuid\Uuid;

$connection = new AMQPStreamConnection('mq', 5672, 'guest', 'guest');
$channel = $connection->channel();

$exchangeName = 'events';
$channel->exchange_declare($exchangeName, 'topic', false, false, false);

$exit = false;
// signal handler function
$signalHandler = function ($signo) use (&$exit) {
    echo "Signal received...shutting down...\n";
    $exit = true;
};

pcntl_signal(SIGTERM, $signalHandler);

while (!$exit) {
    $data = [
        'event' => 'OrderPlaced',
        'orderId' => Uuid::uuid1()->toString(),
        'lines' => [],
    ];

    foreach (range(1, mt_rand(1, 8)) as $lineIdx) {
        $data['lines'][] = [
            'productId' => mt_rand(1, 999999),
            'quantity' => mt_rand(1, 20),
        ];
    }

    $headers = new Wire\AMQPTable([
        'correlation-id' => Uuid::uuid1()->toString(),
    ]);
    $msg = new AMQPMessage(
        json_encode($data),
        [
            'content_type' => 'application/json',
            'application_headers' => $headers,
        ]
    );

    $channel->basic_publish($msg, $exchangeName, 'order.placed');
    echo "Placing order ${data['orderId']}\n";
    sleep(5);
}

$channel->close();
$connection->close();
