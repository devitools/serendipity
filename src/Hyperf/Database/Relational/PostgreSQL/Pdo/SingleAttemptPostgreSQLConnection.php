<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Closure;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\PgSQL\PostgreSqlConnection;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class SingleAttemptPostgreSQLConnection extends PostgreSqlConnection
{
    private ?Throwable $failure = null;

    public function __construct(
        PDO|Closure $pdo,
        string $database = '',
        string $prefix = '',
        array $config = [],
        private readonly PdoFailureClassifier $classifier = new PdoFailureClassifier(),
    ) {
        parent::__construct($pdo, $database, $prefix, $config);
    }

    public function isInvalid(): bool
    {
        return $this->failure !== null;
    }

    public function withPdo(Closure $callback): void
    {
        $this->guard();
        try {
            $callback($this->getPdo());
        } catch (PDOException $exception) {
            $this->recordFailure($exception);
            throw $exception;
        }
    }

    public function commit(): void
    {
        $this->withPdo(fn () => parent::commit());
    }

    public function rollBack($toLevel = null): void
    {
        if ($this->isInvalid()) {
            $this->transactions = 0;
            return;
        }

        parent::rollBack($toLevel);
    }

    public function transaction(Closure $callback, int $attempts = 1): mixed
    {
        if ($attempts !== 1) {
            throw new InvalidArgumentException('The PDO backend only supports a single transaction attempt.');
        }
        $this->beginTransaction();
        try {
            $result = $callback($this);
        } catch (Throwable $exception) {
            try {
                $this->rollBack();
            } catch (Throwable) {
            }
            throw $exception;
        }
        $this->commit();

        return $result;
    }

    protected function handleQueryException(
        QueryException $exception,
        string $query,
        array $bindings,
        Closure $callback
    ): never {
        $this->recordFailure($exception->getPrevious() ?? $exception);
        throw $exception;
    }

    protected function handleBeginTransactionException($exception): never
    {
        $this->recordFailure($exception);
        throw $exception;
    }

    protected function handleRollBackException($exception): never
    {
        $this->failure ??= $exception;
        $this->transactions = 0;
        throw $exception;
    }

    protected function reconnectIfMissingConnection(): void
    {
        $this->guard();
        parent::reconnectIfMissingConnection();
    }

    private function guard(): void
    {
        if ($this->failure !== null) {
            throw new RuntimeException(
                'PostgreSQL session is invalid; operation was not executed. Release it before acquiring again.',
                0,
                $this->failure
            );
        }
    }

    private function recordFailure(Throwable $exception): void
    {
        if ($this->classifier->isConnectionFailure($exception)) {
            $this->failure ??= $exception;
        }
    }
}
