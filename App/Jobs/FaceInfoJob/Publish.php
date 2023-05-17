<?php

namespace App\Jobs\FaceInfoJob;

use DevCoder\DotEnv;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Publish
{
    /**
     * @var AMQPChannel
     */
    private AMQPChannel $channel;
    /**
     * @var AMQPStreamConnection
     */
    private AMQPStreamConnection $connection;

    public function __construct()
    {
        $absolutePathToEnvFile = '/var/www/.env';
        (new DotEnv($absolutePathToEnvFile))->load();

        $host = getenv('RABBITMQ_HOST');
        $port = getenv('RABBITMQ_PORT');
        $user = getenv('RABBITMQ_USER');
        $pass = getenv('RABBITMQ_PASSWORD');

        $this->connection = new AMQPStreamConnection($host, $port, $user, $pass);
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('jobs', 'fanout', false, false, false);
    }

    /**
     * @param int $id
     * @return void
     */
    public function dispatch(int $id)
    {
        $message = new AMQPMessage(json_encode(['id' => $id]));
        $this->channel->basic_publish($message, 'jobs');

        $this->channel->close();
        $this->connection->close();
    }
}