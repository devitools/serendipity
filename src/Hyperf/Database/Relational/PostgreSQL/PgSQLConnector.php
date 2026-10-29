<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Swoole\Coroutine\PostgreSQL;
use Throwable;

use function Constructo\Cast\stringify;
use function sprintf;

class PgSQLConnector
{
    public function connect(array $config, PgSQLSession $session): PostgreSQL
    {
        $driver = $this->create();
        try {
            $this->initialize($driver, $config, $session);
            $this->schema($driver, $config, $session);
            return $driver;
        } catch (Throwable $exception) {
            $session->clean('connect', static function () use (&$driver): void {
                $driver = null;
            });
            throw $exception;
        }
    }

    public function create(): PostgreSQL
    {
        return new PostgreSQL();
    }

    private function initialize(PostgreSQL $driver, array $config, PgSQLSession $session): void
    {
        $dsn = sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s',
            stringify($config['host']),
            stringify($config['port']),
            stringify($config['database']),
            stringify($config['username']),
            stringify($config['password']),
        );
        if ($session->call('connect', $driver, $driver, fn () => $driver->connect($dsn)) === false) {
            $session->failure('connect', $driver, $driver);
        }
    }

    private function schema(PostgreSQL $driver, array $config, PgSQLSession $session): void
    {
        if (! empty($config['schema'])) {
            $query = sprintf('set search_path to %s', stringify($config['schema']));
            if ($session->call('schema', $driver, $driver, fn () => $driver->query($query)) === false) {
                $session->failure('schema', $driver, $driver);
            }
        }
    }
}
