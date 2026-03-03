<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQPublisher
{
    protected ?AMQPStreamConnection $connection = null;
    protected $channel = null;
    protected string $defaultExchange;

    public function __construct()
    {
        $this->defaultExchange = config('rabbitmq.exchange');
    }

    protected function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.username'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost')
        );

        $this->channel = $this->connection->channel();
    }

    protected function ensureConnection(): void
    {
        if (
            !$this->connection ||
            !$this->connection->isConnected() ||
            !$this->channel ||
            !$this->channel->is_open()
        ) {
            $this->connect();
        }
    }

    //kalau mau safety (auto create exchange jika belum ada), rekomendasi lebih baik jangan digunakan
    protected function declareExchange(string $exchange, string $type = 'direct'): void
    {
        $this->channel->exchange_declare(
            $exchange,
            $type,
            false,
            true,
            false
        );
    }

    public function publish(
        array $payload,
        string $routingKey,
        string $exchangeType = 'topic',
        ?string $exchange = null
        
    ): void {
        try {
            $this->ensureConnection();

            $exchange = $exchange ?? $this->defaultExchange;

            $message = new AMQPMessage(
                json_encode([$payload], JSON_THROW_ON_ERROR),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $this->channel->basic_publish(
                $message,
                $exchange,
                $routingKey
            );
        } catch (\Throwable $e) {
            Log::chanel('rabbitmq')->error('RabbitMQ publish error', [
                'error' => $e->getMessage(),
                'exchange' => $exchange ?? $this->defaultExchange,
                'routing_key' => $routingKey,
            ]);
        }
    }
}
