<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Infrastructure;

class TopologyManager
{
    protected $channel;

    public function __construct($channel)
    {
        $this->channel = $channel;
    }

    public function declare(string $queue): void
    {
        $retryQueue = $queue . '.retry';
        $dlq = $queue . '.dlq';

        $ttl = config('rabbitmq.retry.ttl', 10000);

        $this->channel->queue_declare(
            $retryQueue,
            false,
            true,
            false,
            false,
            false,
            [
                'x-message-ttl' => ['I', $ttl],
                'x-dead-letter-exchange' => ['S', ''],
                'x-dead-letter-routing-key' => ['S', $queue]
            ]
        );

        $this->channel->queue_declare(
            $dlq,
            false,
            true,
            false,
            false
        );
    }
}