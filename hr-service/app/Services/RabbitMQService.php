<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    private ?AMQPStreamConnection $connection = null;
    private ?\PhpAmqpLib\Channel\AMQPChannel $channel = null;

    public function __construct()
    {
        //
    }

    /**
     * Get or create a RabbitMQ connection.
     */
    private function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                host: config('rabbitmq.host', 'rabbitmq'),
                port: (int) config('rabbitmq.port', 5672),
                user: config('rabbitmq.user', 'guest'),
                password: config('rabbitmq.password', 'guest'),
                vhost: config('rabbitmq.vhost', '/'),
                connection_timeout: 10.0,
                read_write_timeout: 30.0,
                heartbeat: 15,
            );
        }

        return $this->connection;
    }

    /**
     * Get or create a channel.
     */
    private function getChannel(): \PhpAmqpLib\Channel\AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->channel = $this->getConnection()->channel();
        }

        return $this->channel;
    }

    /**
     * Publish a message to the specified exchange.
     */
    public function publish(string $exchange, string $routingKey, array $message): void
    {
        $channel = $this->getChannel();

        // Declare the topic exchange (idempotent)
        $channel->exchange_declare(
            exchange: $exchange,
            type: AMQPExchangeType::TOPIC,
            passive: false,
            durable: true,
            auto_delete: false,
        );

        $amqpMessage = new AMQPMessage(
            json_encode($message),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp' => time(),
                'message_id' => $message['event_id'] ?? uniqid('msg_', true),
            ]
        );

        $channel->basic_publish(
            msg: $amqpMessage,
            exchange: $exchange,
            routing_key: $routingKey,
        );

        Log::debug('RabbitMQ message published', [
            'exchange' => $exchange,
            'routing_key' => $routingKey,
        ]);
    }

    /**
     * Close connection on destruct.
     */
    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable $e) {
            // Silently ignore close errors
        }
    }
}
