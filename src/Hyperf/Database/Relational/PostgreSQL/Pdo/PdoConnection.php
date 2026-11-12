<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Closure;
use Constructo\Support\Set;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\DbConnection\Connection as PooledConnection;
use LogicException;
use Serendipity\Infrastructure\Database\Relational\Connection;
use Swoole\Runtime;

final readonly class PdoConnection implements Connection
{
    public function __construct(
        public string $connection,
        private ConnectionResolverInterface $resolver,
    ) {
    }

    public function beginTransaction(): void
    {
        $this->database()->beginTransaction();
    }

    public function commit(): void
    {
        $this->database()->commit();
    }

    public function rollback(): void
    {
        $this->database()->rollBack();
    }

    public function insert(string $query, array $bindings = []): int
    {
        return (int) $this->database()->insert($query, $bindings);
    }

    public function execute(string $query, array $bindings = []): int
    {
        return $this->database()->affectingStatement($query, $bindings);
    }

    public function query(string $query, array $bindings = []): array
    {
        /** @var array<array<string, mixed>> $rows */
        $rows = array_map(static fn ($row): array => (array) $row, $this->database()->select($query, $bindings, false));
        return $rows;
    }

    public function fetch(string $query, array $bindings = []): Set
    {
        return Set::createFrom((array) $this->database()->selectOne($query, $bindings, false));
    }

    public function run(Closure $closure): void
    {
        $this->database()->__call('withPdo', [$closure]);
    }

    private function database(): PooledConnection
    {
        if (! $this->hasCoroutineRuntime()) {
            throw new LogicException('The PDO PostgreSQL backend requires a coroutine and SWOOLE_HOOK_PDO_PGSQL.');
        }

        $database = $this->resolver->connection($this->connection);
        if (! $database instanceof PooledConnection) {
            throw new LogicException('The PDO PostgreSQL backend requires the Hyperf pooled connection resolver.');
        }

        return $database;
    }

    private function hasCoroutineRuntime(): bool
    {
        return Coroutine::inCoroutine() && defined('SWOOLE_HOOK_PDO_PGSQL')
            && (Runtime::getHookFlags() & constant('SWOOLE_HOOK_PDO_PGSQL')) !== 0;
    }
}
