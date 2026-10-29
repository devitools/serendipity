<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Closure;
use ErrorException;
use Hyperf\DB\AbstractConnection;
use Swoole\Coroutine\PostgreSQL;
use Throwable;

use function str_contains;

final class PgSQLCommands
{
    public function call(PgSQLSession $session, ?PostgreSQL $driver, string $method, Closure $callback): mixed
    {
        $driver = $session->usable($driver);
        $result = $session->call($method, $driver, $driver, $callback);
        if ($result === false) {
            $session->failure($method, $driver, $driver);
        }
        return $result;
    }

    public function run(PgSQLSession $session, ?PostgreSQL $driver, Closure $callback): mixed
    {
        $driver = $session->usable($driver);
        try {
            return $callback();
        } catch (Throwable $exception) {
            if ($exception instanceof ErrorException && str_contains($exception->getMessage(), PostgreSQL::class)) {
                $session->inspect('run', $driver, $driver, $exception);
            }
            throw $exception;
        } finally {
            $session->inspect('run', $driver, $driver);
        }
    }

    /** @param Closure(): PgSQLFailure $snapshot */
    public function release(
        PgSQLSession $session,
        AbstractConnection $connection,
        Closure $snapshot,
        Closure $release,
    ): void {
        $this->rollback($session, $connection, $snapshot);
        if (! $session->isValid()) {
            $session->discard(fn () => $connection->close());
        }
        $session->clean('release', $release);
    }

    /** @param Closure(): PgSQLFailure $snapshot */
    private function rollback(PgSQLSession $session, AbstractConnection $connection, Closure $snapshot): void
    {
        try {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack(0);
            }
        } catch (Throwable $exception) {
            $session->invalidate('release', $snapshot(), 'rollback_failed');
            $session->clean('rollback', static fn () => throw $exception);
            $connection->rollBack(0);
        }
    }
}
