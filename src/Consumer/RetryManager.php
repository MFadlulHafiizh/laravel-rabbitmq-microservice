<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Consumer;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RetryManager
{
    protected $channel;
    protected int $maxAttempts;

    public function __construct($channel)
    {
        $this->channel = $channel;
        $this->maxAttempts = config('rabbitmq.retry.max_attempts', 3);
    }

    public function getRetryCount(AMQPMessage $msg): int
    {
        $headers = $msg->get('application_headers');

        if (!$headers) {
            return 0;
        }

        $data = $headers->getNativeData();

        return $data['x-retry-count'] ?? 0;
    }

    public function canRetry(int $retryCount): bool
    {
        return $retryCount < $this->maxAttempts;
    }

    public function retry(AMQPMessage $msg, string $queue, int $retryCount): int
    {
        $retryCount++;

        $retryQueue = $queue . '.retry';

        $message = new AMQPMessage(
            $msg->body,
            [
                'delivery_mode' => 2,
                'application_headers' => new AMQPTable([
                    'x-retry-count' => $retryCount
                ])
            ]
        );

        $this->channel->basic_publish(
            $message,
            '',
            $retryQueue
        );

        return $retryCount;
    }

    public function sendToDLQ(AMQPMessage $msg, string $queue): void
    {
        $dlq = $queue . '.dlq';

        $message = new AMQPMessage(
            $msg->body,
            ['delivery_mode' => 2]
        );

        $this->channel->basic_publish(
            $message,
            '',
            $dlq
        );
    }
}