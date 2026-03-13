<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Consumer;

use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Output\OutputInterface;

class MessageHandler
{
    protected $listener;
    protected $channel;
    protected string $queue;
    protected OutputInterface $output;

    public function __construct($listener, $channel, string $queue, OutputInterface $output)
    {
        $this->listener = $listener;
        $this->channel = $channel;
        $this->queue = $queue;
        $this->output = $output;
    }

    public function __invoke(AMQPMessage $msg)
    {
        $this->output->write("Running {$this->queue} ... ");

        $retryManager = new RetryManager($this->channel);

        try {

            $payload = json_decode($msg->body, true);

            $this->listener->handle($payload);

            $msg->ack();

            $this->output->writeln("<info>SUCCESS</info>");

        } catch (\Throwable $e) {

            $retryCount = $retryManager->getRetryCount($msg);

            if (
                config('rabbitmq.retry.enabled', true)
                && $retryManager->canRetry($retryCount)
            ) {

                $newRetry = $retryManager->retry($msg, $this->queue, $retryCount);

                $this->output->writeln("<comment>RETRY {$newRetry}</comment>");

            } else {

                $retryManager->sendToDLQ($msg, $this->queue);

                $this->output->writeln("<error>FAILED → DLQ</error>");
            }

            $msg->ack();

            $this->output->writeln("<error>{$e->getMessage()}</error>");
            $this->output->writeln("<comment>{$e->getFile()}:{$e->getLine()}</comment>");

            logger()->error('RabbitMQ Listener Failed', [
                'queue' => $this->queue,
                'message' => $msg->body,
                'retry_count' => $retryCount,
                'exception' => $e
            ]);
        }
    }
}