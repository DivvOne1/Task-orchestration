<?php

namespace App\Services;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqPublisher
{
    public function publish(array $payload, ?string $queue = null): void
    {
        $connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost'),
        );

        /** @var AMQPChannel $channel */
        $channel = $connection->channel();
        $queueName = $queue ?? config('rabbitmq.queue');

        $channel->queue_declare($queueName, false, true, false, false);

        $message = new AMQPMessage(
            json_encode($payload, JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => 2,
            ],
        );

        $channel->basic_publish($message, '', $queueName);

        $channel->close();
        $connection->close();
    }
}
