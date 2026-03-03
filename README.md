# MCS Laravel RabbitMQ

RabbitMQ Publisher & Consumer for Laravel Microservices Architecture.

Supports:
- Lazy connection (safe for package boot)
- Topic / Direct exchange publishing
- Queue-based consumer
- Artisan listener generator
- Production-ready worker with Supervisor

---

# 🇺🇸 English Documentation

## Installation

```bash
composer require kuncen/mcs-laravel-rabbitmq
```

---

## Publish Configuration

```bash
php artisan vendor:publish --provider="Kuncen\McsRabbitMQ\RabbitMQServiceProvider"
```

This will publish:

```
config/rabbitmq.php
```

---

## Environment Configuration

Add to `.env`:

```env
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USERNAME=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_EXCHANGE=
```

---

# Publishing Messages

## Inject Publisher

```php
use Kuncen\MCSLaravel\RabbitMQ\Services\RabbitMQPublisher;

class UserController extends Controller
{
    public function store(RabbitMQPublisher $publisher)
    {
        $publisher->publish(
            payload: [
                'user_id' => 1,
                'name' => 'John Doe'
            ],
            routingKey: 'user.created'
        );

        return response()->json(['status' => 'Message sent']);
    }
}
```

---

## Publisher Method Signature

```php
publish(
    array $payload,
    string $routingKey,
    string $exchangeType = 'topic',
    ?string $exchange = null
)
```

---

# Listening (Consumer)

## Create Listener

```bash
php artisan make:rabbitlistener UserCreatedListener
```

Generated example:

```php
class UserCreatedListener implements RabbitMQListener
{
    public static function queue(): string
    {
        return 'user.created';
    }

    public function handle(array $payload): void
    {
        // Your business logic here
    }
}
```

---

## Run Worker

```bash
php artisan rabbitmq:consume UserCreatedListener
```

Worker will listen to:

```
user.created
```

---

# Production Setup (Supervisor)

Create file:

```
/etc/supervisor/conf.d/rabbitmq-user-created.conf
```

Content:

```ini
[program:rabbitmq-user-created]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-project/artisan rabbitmq:consume UserCreatedListener
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path-to-project/storage/logs/rabbitmq-user-created.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start rabbitmq-user-created
```

---

# Best Practices

- Do not auto-declare exchange in production.
- Let infrastructure manage queue arguments.
- Use Supervisor for production workers.
- Recommended: 1 queue = 1 listener.

---

# 🇮🇩 Dokumentasi Bahasa Indonesia

## Instalasi

```bash
composer require kuncen/mcs-laravel-rabbitmq
```

---

## Publish Konfigurasi

```bash
php artisan vendor:publish --provider="Kuncen\McsRabbitMQ\RabbitMQServiceProvider"
```

File yang akan dibuat:

```
config/rabbitmq.php
```

---

## Konfigurasi .env

Tambahkan:

```env
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USERNAME=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_EXCHANGE=
```

---

# Mengirim Pesan (Publish)

## Inject Publisher ke Controller

```php
use Kuncen\MCSLaravel\RabbitMQ\Services\RabbitMQPublisher;

class UserController extends Controller
{
    public function store(RabbitMQPublisher $publisher)
    {
        $publisher->publish(
            payload: [
                'user_id' => 1,
                'name' => 'John Doe'
            ],
            routingKey: 'user.created'
        );

        return response()->json(['status' => 'Message sent']);
    }
}
```

---

## Signature Method

```php
publish(
    array $payload,
    string $routingKey,
    string $exchangeType = 'topic',
    ?string $exchange = null
)
```

---

# Listening / Consumer

## Membuat Listener

```bash
php artisan make:rabbitlistener UserCreatedListener
```

Contoh isi listener:

```php
class UserCreatedListener implements RabbitMQListener
{
    public static function queue(): string
    {
        return 'user.created';
    }

    public function handle(array $payload): void
    {
        // Logic bisnis di sini
    }
}
```
---

## Menjalankan Worker

```bash
php artisan rabbitmq:consume UserCreatedListener
```

Worker akan listen ke queue:

```
user.created
```

---

# Setup Production (Supervisor)

Buat file:

```
/etc/supervisor/conf.d/rabbitmq-user-created.conf
```

Isi:

```ini
[program:rabbitmq-user-created]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-project/artisan rabbitmq:consume UserCreatedListener
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path-to-project/storage/logs/rabbitmq-user-created.log
stopwaitsecs=3600
```

Reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start rabbitmq-user-created
```

---

# Rekomendasi

- Hindari auto declare exchange di production.
- Biarkan konfigurasi queue dikelola oleh infrastructure.
- Gunakan Supervisor untuk worker.
- Disarankan 1 queue = 1 listener untuk arsitektur microservice.

---

# License

MIT