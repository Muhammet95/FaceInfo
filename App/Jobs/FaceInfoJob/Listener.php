<?php

use App\Services\RequestService;
use \PhpAmqpLib\Connection\AMQPStreamConnection;

require_once 'vendor/autoload.php';

try {
    $connection = new AMQPStreamConnection('face-info-rabbitmq', '5672', 'guest', 'guest');
    $channel = $connection->channel();

    $channel->queue_declare('',false, false, false, false);
    $channel->exchange_declare('jobs', 'fanout', false, false, false);
    list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
    $channel->queue_bind($queue_name, 'jobs');

    $callback = function ($msg) {
        echo "Start working\n";
        $data = json_decode($msg->body, true);
        if (isset($data['id']) && ($data['id'] == (int)$data['id']))
            (new RequestService($data['id']))->process();
        else
            echo "No id\n";
        echo "Stop working\n";
    };

    $channel->basic_consume($queue_name, '', false, true, false, false, $callback);

    while ($channel->is_open()) {
        $channel->wait();
    }

} catch (Exception $exception) {
    echo "Got an error: " . $exception->getMessage() . PHP_EOL;
}