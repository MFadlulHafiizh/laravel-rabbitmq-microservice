<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Consumer;

class RetryPolicy
{
    protected int $maxAttempts;

    public function __construct(int $maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
    }

    public function canRetry(int $currentRetry): bool
    {
        return $currentRetry < $this->maxAttempts;
    }
}