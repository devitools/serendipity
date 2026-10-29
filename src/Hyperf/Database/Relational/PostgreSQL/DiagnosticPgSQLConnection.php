<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Closure;
use Hyperf\DB\Exception\QueryException;
use Hyperf\DB\PgSQL\PgSQLConnection;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;
use Throwable;

use function microtime;

class DiagnosticPgSQLConnection extends PgSQLConnection
{
    public function __construct(
        ContainerInterface $container,
        Pool $pool,
        array $config,
        private readonly PgSQLSession $session,
        private readonly PgSQLConnector $connector,
        private readonly PgSQLStatements $statements,
        private readonly PgSQLCommands $commands,
    ) {
        parent::__construct($container, $pool, $config);
    }

    public function check(): bool
    {
        return $this->session->isValid() && isset($this->connection) && ($this->transactions > 0 || parent::check());
    }

    public function getConnection(): static
    {
        if ($this->transactions > 0 && ! $this->check()) {
            return $this;
        }
        $this->getActiveConnection();
        return $this;
    }

    public function reconnect(): bool
    {
        if ($this->transactions > 0) {
            throw new QueryException('Cannot reconnect an active PostgreSQL transaction.');
        }
        $this->session->discard(fn () => parent::close());
        $this->connection = $this->connector->connect($this->config, $this->session);
        $this->session->activate();
        $this->lastUseTime = microtime(true);
        return true;
    }

    public function execute(string $query, array $bindings = []): int
    {
        return $this->statements->execute($this->session, $this->connection ?? null, $query, $bindings);
    }

    public function query(string $query, array $bindings = []): array
    {
        return $this->statements->query($this->session, $this->connection ?? null, $query, $bindings);
    }

    public function call(string $method, array $argument = []): mixed
    {
        $callback = fn () => parent::call($method, $argument);
        return $this->commands->call($this->session, $this->connection ?? null, $method, $callback);
    }

    public function run(Closure $closure): mixed
    {
        return $this->commands->run($this->session, $this->connection ?? null, fn () => parent::run($closure));
    }

    public function rollBack(?int $toLevel = null): void
    {
        if (! $this->session->isValid() || ! isset($this->connection)) {
            $this->transactions = 0;
            $this->session->discard(fn () => parent::close());
            return;
        }
        parent::rollBack($toLevel);
    }

    public function release(): void
    {
        $snapshot = fn () => PgSQLFailure::from($this->connection ?? null);
        $this->commands->release($this->session, $this, $snapshot, fn () => parent::release());
    }

    protected function causedByLostConnection(Throwable $exception): bool
    {
        return false;
    }
}
