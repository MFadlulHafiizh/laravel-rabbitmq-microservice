<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Contracts;

interface RabbitMQListener
{
    public static function queue(): string;

    public function handle(array $payload): void;
}