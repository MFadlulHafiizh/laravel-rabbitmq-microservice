<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Services;

use Kuncen\MCSLaravel\RabbitMQ\Contracts\RabbitMQListener;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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

        $output = new ConsoleOutput();
        $output->writeln("Listening on queue: {$queue}");

        $listener = app($listenerClass);

        $channel->basic_qos(null, 1, null);

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($listener, $output, $queue) {

                $output->write("Running {$queue} ... ");

                try {
                    $listener->handle(json_decode($msg->body, true));
                    $msg->ack();
                    $output->writeln("<info>SUCCESS</info>");
                } catch (\Throwable $e) {
                    $msg->nack(false, false);
                    $output->writeln("<error>FAILED</error>");
                    $output->writeln("");
                    $output->writeln("<error>{$e->getMessage()}</error>");
                    $output->writeln("<comment>{$e->getFile()}:{$e->getLine()}</comment>");

                    $output->writeln("<comment>{$e->getTraceAsString()}</comment>");

                    logger()->error('RabbitMQ Listener Failed', [
                        'queue' => $queue,
                        'message' => $msg->body,
                        'exception' => $e
                    ]);
                }
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}