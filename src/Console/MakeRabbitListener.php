<?php

namespace Kuncen\MCSLaravel\RabbitMQ\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeRabbitListener extends Command
{
    protected $signature = 'make:rabbitlistener {name}';
    protected $description = 'Create a new RabbitMQ Listener class';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));

        if (!Str::endsWith($name, 'Listener')) {
            $name .= 'Listener';
        }

        $namespace = 'App\\Services\\RabbitMQ';
        $directory = app_path('Services/RabbitMQ');
        $path = $directory . '/' . $name . '.php';

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Listener already exists!");
            return;
        }

        $stub = $this->buildStub($namespace, $name);

        (new Filesystem)->put($path, $stub);

        $this->info("RabbitMQ Listener created: {$name}");
        $this->line("Path: {$path}");
    }

    protected function buildStub(string $namespace, string $className): string
    {
        $queueName = Str::of($className)
            ->beforeLast('Listener')
            ->kebab('.');

        return <<<PHP
            <?php

            namespace {$namespace};

            use Kuncen\MCSLaravel\RabbitMQ\Contracts\RabbitMQListener;
            use Illuminate\Support\Facades\Log;

            class {$className} implements RabbitMQListener
            {
                /**
                 * Return queue suffix name (tanpa service_name prefix)
                 */
                public static function queue(): string
                {
                    return '{$queueName}';
                }

                public function handle(array \$payload): void
                {
                    Log::channel('rabbit_sub')->info(
                        '{$className} received message',
                        \$payload
                    );

                    // TODO: Implement business logic
                }
            }

            PHP;
        }
}