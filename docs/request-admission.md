# Request admission

`AdmissionMiddleware` is opt-in. It calls Hyperf's native `RateLimitHandler` and Redis storage once per admission attempt. A local counter also limits active requests in each worker, including requests waiting for the budget check. No database pool, SQL execution, transaction or provider retry mechanism is changed.

Register the middleware once per HTTP server, after outer tracing/CORS middleware and before authentication that accesses a database, connection checks, controllers or provider work. Hyperf's container must manage its shared worker instance; do not construct a middleware for each request. Health and metrics paths bypass admission by default.

## Configuration

```php
// config/autoload/admission.php
return [
    'enabled' => true,
    'key' => 'quoteguide:dev:integrations:admission',
    'rate' => 4,
    'burst' => 4,
    'concurrency' => 4,
    'excluded_paths' => ['/health', '/metrics'],
];

// config/autoload/rate_limit.php
return [
    'storage' => [
        'class' => Hyperf\RateLimit\Storage\RedisStorage::class,
        'options' => ['pool' => 'admission', 'expired_time' => 60],
    ],
];

// config/autoload/redis.php
return [
    'admission' => [
        'host' => env('ADMISSION_KV_HOST'),
        'port' => 6379,
        'db' => 0,
        'timeout' => 0.1,
        'read_timeout' => 0.1,
        'retry_interval' => 0,
        'pool' => [
            'min_connections' => 0,
            'max_connections' => 4,
            'connect_timeout' => 0.1,
            'wait_timeout' => 0.05,
            'heartbeat' => -1,
            'max_idle_time' => 60,
        ],
    ],
];
```

The default is disabled. Enabling admission requires a nonempty key and positive rate, burst and concurrency. All workers and instances serving one environment must use the same key and KV primary endpoint. Use a different key and preferably a separate instance for each environment. Key prefixes are not a security boundary.

The initial budget is **four tokens per second, with four burst tokens shared across instances**, plus **four active requests per worker**. This is an initial protection setting, not a proven production capacity. A rate limit controls arrivals; the concurrency limit remains necessary when admitted requests are slow.

Register `Serendipity\Hyperf\Middleware\AdmissionMiddleware::class` in the consuming application's HTTP middleware configuration. No middleware or application configuration is installed automatically by this package.

## Refusal and failure behavior

- No available local slot or shared token: return `429`, `Retry-After: 1`, `Cache-Control: no-store` and `{"code":"capacity_exceeded","processed":false}`. The downstream handler is never called, and a structured WARNING records the refusal reason and status.
- Local concurrency refusal is immediate. Hyperf's native distributed mutex can wait up to one second; its handler requires an integer timeout greater than zero. No loop waits for tokens to refill. Mutex contention produces a refusal, not a KV outage fallback.
- KV transport, storage or connection-pool failure: keep local concurrency protection, log one WARNING on entry into degraded mode and add `Admission-Degraded: true` to successful downstream responses. The global rate is not enforced while degraded. One request per worker probes after five seconds; other requests use local protection during that probe. Recovery logs INFO.
- Exceptions from the downstream handler propagate unchanged, and local slots are released in `finally`. An admitted operation is invoked once. This middleware never retries SQL, COMMIT, HTTP provider calls or the business handler. Hyperf's Redis client retains its native reconnect/retry behavior for KV commands; `retry_interval=0` does not disable that behavior.

`Retry-After` is a client hint. [Cloud Tasks still applies its existing queue retry and congestion policies](https://docs.cloud.google.com/tasks/docs/common-pitfalls), so this header does not promise redelivery after exactly one second. No queue, acknowledgment or lead delivery state is modified.

Diagnostics omit payloads, keys, connection strings and exception messages. Admission refusals are expected under overload and must be reported separately from processing failures. Tracing must surround this middleware so refusals are observable too.

## Validation

The tests use a local Valkey container with a pinned image digest. They exercise a real shared budget across independent instances, real mutex contention and an unreachable loopback endpoint, alongside coroutine tests for the four-slot bound, recovery probes, unchanged exceptions and zero downstream calls on refusal. They never contact a provider or a cloud KV.

Run `make ci` with the existing PostgreSQL, MongoDB and Valkey services available. Consumers must separately validate traces, environment isolation, their measured load and failure behavior in staging before production promotion.
