<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Closure;
use Swoole\Coroutine\PostgreSQL as Client;
use Swoole\Coroutine\PostgreSQLStatement as Statement;
use Throwable;

use function Constructo\Cast\arrayify;
use function Constructo\Cast\integerify;
use function strpos;
use function substr_replace;

class PgSQLStatements
{
    public function execute(PgSQLSession $session, ?Client $driver, string $query, array $bindings): int
    {
        return integerify($this->result($session, $session->usable($driver), $query, $bindings, 'affected_rows'));
    }

    public function query(PgSQLSession $session, ?Client $driver, string $query, array $bindings): array
    {
        return arrayify($this->result($session, $session->usable($driver), $query, $bindings, 'fetch'));
    }

    private function result(PgSQLSession $session, Client $driver, string $query, array $bindings, string $stage): mixed
    {
        $statement = $this->prepare($session, $driver, $query);
        $failure = null;
        try {
            $this->executePrepared($session, $driver, $statement, $bindings);
            return $this->read($session, $driver, $statement, $stage);
        } catch (Throwable $exception) {
            $failure = $exception;
            throw $exception;
        } finally {
            $this->finish($session, $driver, static function () use (&$statement): void {
                $statement = null;
            }, $failure);
        }
    }

    private function prepare(PgSQLSession $session, Client $driver, string $query): Statement
    {
        $num = 1;
        while (($position = strpos($query, '?')) !== false) {
            $query = substr_replace($query, '$' . $num++, $position, 1);
        }
        $statement = $session->call('prepare', $driver, $driver, fn () => $driver->prepare($query));
        if (! $statement instanceof Statement) {
            $session->failure('prepare', $driver, $driver);
        }
        return $statement;
    }

    private function executePrepared(PgSQLSession $session, Client $driver, Statement $statement, array $bindings): void
    {
        $result = $session->call('execute', $statement, $driver, fn () => $statement->execute($bindings));
        if ($result === false || ! empty($statement->error)) {
            $session->failure('execute', $statement, $driver);
        }
    }

    private function read(PgSQLSession $session, Client $driver, Statement $statement, string $stage): mixed
    {
        $reader = fn () => $stage === 'fetch' ? $statement->fetchAll() : $statement->affectedRows();
        $result = $session->call($stage, $statement, $driver, $reader);
        if ($result === false) {
            $session->failure($stage, $statement, $driver);
        }
        return $result;
    }

    private function finish(PgSQLSession $session, Client $driver, Closure $dispose, ?Throwable $original): void
    {
        try {
            $session->call('statement_cleanup', $driver, $driver, $dispose);
        } catch (Throwable $exception) {
            $session->invalidate('statement_cleanup', PgSQLFailure::from($driver), 'statement_cleanup_failed');
            if ($original === null) {
                throw $exception;
            }
            $session->clean('statement', static fn () => throw $exception);
        }
    }
}
