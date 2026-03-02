<?php

namespace App\Console\Commands;

use App\Contracts\EventProcessorInterface;
use App\Enums\EventTypesEnum;
use App\EventHandlers\EmployeeCreatedHandler;
use App\EventHandlers\EmployeeDeletedHandler;
use App\EventHandlers\EmployeeUpdatedHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeRabbitMQEvents extends Command
{
    protected $signature = 'rabbitmq:consume';
    protected $description = 'Consume employee events from RabbitMQ';

    private const MAX_RETRIES = 3;

    /**
     * Map of event types to handler classes.
     *
     * @var array<string, class-string<EventProcessorInterface>>
     */
    private array $handlers = [
        EventTypesEnum::EMPLOYEE_CREATED->value => EmployeeCreatedHandler::class,
        EventTypesEnum::EMPLOYEE_UPDATED->value => EmployeeUpdatedHandler::class,
        EventTypesEnum::EMPLOYEE_DELETED->value => EmployeeDeletedHandler::class,
    ];

    public function handle(): int
    {
        $this->info('Starting RabbitMQ consumer...');

        $connection = $this->connect();

        if (!$connection) {
            $this->error('Failed to connect to RabbitMQ after retries.');
            return self::FAILURE;
        }

        try {
            $channel = $connection->channel();

            $exchange = config('rabbitmq.exchange', 'employee_events');
            $channel->exchange_declare(
                exchange: $exchange,
                type: AMQPExchangeType::TOPIC,
                passive: false,
                durable: true,
                auto_delete: false,
            );

            $queue = config('rabbitmq.queue', 'hub_service_queue');
            $channel->queue_declare(
                queue: $queue,
                passive: false,
                durable: true,
                exclusive: false,
                auto_delete: false,
            );

            $channel->queue_bind($queue, $exchange, 'employee.*.*');

            $channel->basic_qos(0, 1, false);

            $this->info("Listening on queue '{$queue}' bound to exchange '{$exchange}'...");

            $channel->basic_consume(
                queue: $queue,
                consumer_tag: 'hub_service_consumer',
                no_local: false,
                no_ack: false,
                exclusive: false,
                nowait: false,
                callback: fn (AMQPMessage $message) => $this->processMessage($message),
            );

            while ($channel->is_consuming()) {
                $channel->wait();
            }
        } catch (\Throwable $e) {
            $this->error("Consumer error: {$e->getMessage()}");
            Log::error('RabbitMQ consumer error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        } finally {
            $connection->close();
        }

        return self::SUCCESS;
    }

    private function processMessage(AMQPMessage $message): void
    {
        $body = $message->getBody();

        try {
            $eventData = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON in RabbitMQ message', ['body' => $body]);
                $message->ack();
                return;
            }

            $eventType = $eventData['event_type'] ?? null;

            Log::info('Received RabbitMQ event', [
                'event_type' => $eventType,
                'event_id' => $eventData['event_id'] ?? 'unknown',
            ]);

            if (!$eventType || !isset($this->handlers[$eventType])) {
                Log::warning('Unknown event type received', ['event_type' => $eventType]);
                $message->ack();
                return;
            }

            /** @var EventProcessorInterface $handler */
            $handler = app($this->handlers[$eventType]);
            $handler->handle($eventData);

            $message->ack();

            $this->info("Processed {$eventType} event successfully.");
        } catch (\Throwable $e) {
            Log::error('Failed to process RabbitMQ message', [
                'error' => $e->getMessage(),
                'body' => $body,
                'trace' => $e->getTraceAsString(),
            ]);

            $headers = $message->get_properties();
            $retryCount = 0;

            if (isset($headers['application_headers'])) {
                $retryCount = $headers['application_headers']->getNativeData()['x-retry-count'] ?? 0;
            }

            if ($retryCount < self::MAX_RETRIES) {
                $message->nack(requeue: true);
                $this->warn("Message requeued (retry {$retryCount}/" . self::MAX_RETRIES . ")");
            } else {
                $message->ack();
                Log::error('Message discarded after max retries', ['body' => $body]);
                $this->error('Message discarded after max retries.');
            }
        }
    }

    private function connect(): ?AMQPStreamConnection
    {
        $maxAttempts = 10;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $attempt++;
                $this->info("Connecting to RabbitMQ (attempt {$attempt}/{$maxAttempts})...");

                return new AMQPStreamConnection(
                    host: config('rabbitmq.host', 'rabbitmq'),
                    port: (int) config('rabbitmq.port', 5672),
                    user: config('rabbitmq.user', 'guest'),
                    password: config('rabbitmq.password', 'guest'),
                    vhost: config('rabbitmq.vhost', '/'),
                    connection_timeout: 10.0,
                    read_write_timeout: 30.0,
                    heartbeat: 15,
                );
            } catch (\Throwable $e) {
                $this->warn("Connection attempt {$attempt} failed: {$e->getMessage()}");
                sleep(3);
            }
        }

        return null;
    }
}
