<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Services;

use Kuncen\MCSLaravel\RabbitMQ\Consumer\MessageHandler;
use Kuncen\MCSLaravel\RabbitMQ\Contracts\RabbitMQListener;
use Kuncen\MCSLaravel\RabbitMQ\Infrastructure\TopologyManager;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Output\ConsoleOutput;

class RabbitMQConsumer
{
    public function start(string $listenerClass): void
    {
        if (!is_subclass_of($listenerClass, RabbitMQListener::class)) {
            throw new \InvalidArgumentException(
                "{$listenerClass} must implement RabbitMQListener"
            );
        }

        $config = config('rabbitmq');

        $queue = $listenerClass::queue();

        $connection = new AMQPStreamConnection(
            $config['connection']['host'],
            $config['connection']['port'],
            $config['connection']['username'],
            $config['connection']['password'],
            $config['connection']['vhost']
        );

        
        $channel = $connection->channel();

        $topology = new TopologyManager($channel);
        $topology->declare($queue);

        $output = new ConsoleOutput();
        $output->writeln("Listening on queue: {$queue}");

        $listener = app($listenerClass);

        $channel->basic_qos(null, 1, null);

        $handler = new MessageHandler($listener, $channel, $queue, $output);
        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            $handler
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}