<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Closure;
use Hyperf\DB\Exception\QueryException;
use Swoole\Coroutine\PostgreSQL as Client;
use Swoole\Coroutine\PostgreSQLStatement as Statement;
use Throwable;

class PgSQLSession
{
    private bool $invalid = true;

    public function __construct(
        private readonly PgSQLFailureClassifier $classifier,
        private readonly PgSQLDiagnostics $diagnostics,
        private readonly string $poolName,
    ) {
    }

    public function isValid(): bool
    {
        return ! $this->invalid;
    }

    public function activate(): void
    {
        $this->invalid = false;
    }

    public function usable(?Client $driver): Client
    {
        if ($this->invalid || $driver === null) {
            throw new QueryException('PostgreSQL session is unusable; the current operation was not executed.');
        }
        return $driver;
    }

    public function call(string $stage, Client|Statement $driver, Client $fallback, Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            $this->inspect($stage, $driver, $fallback, $exception);
            throw $exception;
        }
    }

    public function failure(string $stage, Client|Statement $driver, Client $fallback): never
    {
        $failure = PgSQLFailure::from($driver, $fallback);
        $this->invalidate($stage, $failure, $this->classifier->failureReason($failure));
        throw new QueryException($failure->message($stage));
    }

    public function inspect(string $stage, Client|Statement $driver, Client $fallback, ?Throwable $error = null): void
    {
        $failure = PgSQLFailure::from($driver, $fallback);
        $this->invalidate($stage, $failure, $this->classifier->reason($failure, $error));
    }

    public function invalidate(string $stage, PgSQLFailure $failure, ?string $reason): void
    {
        if ($reason === null || $this->invalid) {
            return;
        }
        $this->invalid = true;
        $this->diagnostics->invalidated($stage, $failure, $reason, $this->poolName);
    }

    public function discard(Closure $close): void
    {
        $this->invalid = true;
        $this->clean('close', $close);
    }

    public function clean(string $stage, Closure $callback): void
    {
        $this->diagnostics->clean($stage, $callback, $this->poolName);
    }
}
