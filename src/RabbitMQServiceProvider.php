<?php

namespace Kuncen\MCSLaravel\RabbitMQ;

use Kuncen\MCSLaravel\RabbitMQ\Services\RabbitMQPublisher;
use Illuminate\Support\ServiceProvider;
use Kuncen\MCSLaravel\RabbitMQ\Console\MakeRabbitListener;
use Kuncen\MCSLaravel\RabbitMQ\Console\RabbitMQConsume;

class RabbitMQServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/rabbitmq.php',
            'rabbitmq'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/rabbitmq.php' =>
                config_path('rabbitmq.php'),
        ], 'rabbitmq-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRabbitListener::class,
                RabbitMQConsume::class
            ]);
        }
    }
}