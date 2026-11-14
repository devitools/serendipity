<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Admission;

use bandwidthThrottle\tokenBucket\storage\StorageException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Exception\SocketPopException;
use Hyperf\RateLimit\Handler\RateLimitHandler;
use InvalidArgumentException;
use malkusch\lock\exception\TimeoutException;
use Psr\Log\LoggerInterface;
use RedisException;
use Throwable;

use function Constructo\Cast\integerify;
use function Constructo\Cast\stringify;

class SharedBudget
{
    private float $degradedUntil = 0.0;

    private bool $probing = false;

    private readonly string $key;

    private readonly int $rate;

    private readonly int $burst;

    public function __construct(
        private readonly RateLimitHandler $limiter,
        ConfigInterface $config,
        private readonly LoggerInterface $logger,
        private readonly MonotonicClock $clock,
    ) {
        $this->key = trim(stringify($config->get('admission.key', '')));
        $this->rate = integerify($config->get('admission.rate', 4));
        $this->burst = integerify($config->get('admission.burst', 4));
    }

    public function consume(): bool
    {
        $this->validate();
        if ($this->skipProbe()) {
            return true;
        }
        $recovering = $this->isDegraded();
        $this->probing = $recovering;
        try {
            $allowed = $this->limiter->build($this->key, $this->rate, $this->burst, 1)->consume(1);
            $this->recover();
            return $allowed;
        } catch (StorageException | ConnectionException | RedisException | SocketPopException $exception) {
            return $this->handleFailure($exception);
        } finally {
            if ($recovering) {
                $this->probing = false;
            }
        }
    }

    public function isDegraded(): bool
    {
        return $this->degradedUntil > 0.0;
    }

    private function validate(): void
    {
        if ($this->key === '' || $this->rate < 1 || $this->burst < 1) {
            throw new InvalidArgumentException('Admission requires a key and positive rate and burst.');
        }
    }

    private function skipProbe(): bool
    {
        return $this->isDegraded() && ($this->probing || $this->clock->now() < $this->degradedUntil);
    }

    private function handleFailure(Throwable $exception): bool
    {
        if ($exception instanceof StorageException && $exception->getPrevious() instanceof TimeoutException) {
            $this->recover();
            return false;
        }
        return $this->degrade($exception);
    }

    private function degrade(Throwable $exception): bool
    {
        $firstFailure = ! $this->isDegraded();
        $this->degradedUntil = $this->clock->now() + 5;
        if ($firstFailure) {
            $this->logger->warning('Shared admission budget unavailable; using local concurrency protection', [
                'component' => 'admission',
                'degraded' => true,
                'exception_class' => $exception::class,
                'exception_code' => $exception->getCode(),
            ]);
        }
        return true;
    }

    private function recover(): void
    {
        if ($this->isDegraded()) {
            $this->degradedUntil = 0.0;
            $this->logger->info('Shared admission budget recovered', ['component' => 'admission']);
        }
    }
}
