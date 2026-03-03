<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Console;

use Illuminate\Console\Command;
use Kuncen\MCSLaravel\RabbitMQ\Services\RabbitMQConsumer;

class RabbitMQConsume extends Command
{
    protected $signature = 'rabbitmq:consume {listener}';
    protected $description = 'Consume RabbitMQ queue based on listener class';

    public function handle(RabbitMQConsumer $consumer)
    {
        $listenerInput = $this->argument('listener');

        $baseNamespace = 'App\\Services\\RabbitMQ\\';
        $listenerClass = str_contains($listenerInput, '\\')
            ? $listenerInput
            : $baseNamespace . $listenerInput;
        if (!class_exists($listenerClass)) {
            $this->error("Listener class not found: {$listenerClass}");
            return;
        }

        $this->info("Starting consumer for: {$listenerClass}");
        $consumer->start($listenerClass);
    }
}