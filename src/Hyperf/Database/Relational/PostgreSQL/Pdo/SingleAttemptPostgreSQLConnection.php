<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Closure;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\PgSQL\PostgreSqlConnection;
use InvalidArgumentException;
use PDO;
use PDOException;
use Throwable;

class SingleAttemptPostgreSQLConnection extends PostgreSqlConnection
{
    private readonly PdoSessionState $session;

    public function __construct(
        PDO|Closure $pdo,
        string $database = '',
        string $prefix = '',
        array $config = [],
        PdoFailureClassifier $classifier = new PdoFailureClassifier(),
    ) {
        $this->session = new PdoSessionState($classifier);
        parent::__construct($pdo, $database, $prefix, $config);
    }

    public function isInvalid(): bool
    {
        return $this->session->isInvalid();
    }

    public function requiresDiscard(): bool
    {
        return $this->isInvalid() || ($this->pdo instanceof PDO && $this->pdo->inTransaction());
    }

    public function withPdo(Closure $callback): void
    {
        $this->session->guard();
        try {
            $callback($this->getPdo());
        } catch (PDOException $exception) {
            $this->session->recordFailure($exception);
            throw $exception;
        }
    }

    public function commit(): void
    {
        try {
            $this->withPdo(fn () => parent::commit());
        } catch (PDOException $exception) {
            if (! $this->requiresDiscard()) {
                $this->transactions = 0;
            }
            throw $exception;
        }
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
        $this->session->recordFailure($exception->getPrevious() ?? $exception);
        throw $exception;
    }

    protected function handleBeginTransactionException($exception): never
    {
        $this->session->recordFailure($exception);
        throw $exception;
    }

    protected function createSavepoint(): void
    {
        $this->withPdo(fn () => parent::createSavepoint());
    }

    protected function handleRollBackException($exception): never
    {
        $this->session->invalidate($exception);
        $this->transactions = 0;
        throw $exception;
    }

    protected function reconnectIfMissingConnection(): void
    {
        $this->session->guard();
        parent::reconnectIfMissingConnection();
    }
}
