<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Database\Connectors\ConnectionFactory as DatabaseConnectionFactory;
use Hyperf\DbConnection\Pool\DbPool;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionChecker;
use Serendipity\Infrastructure\Database\Relational\ConnectionChecker;
use Serendipity\Infrastructure\Database\Relational\ConnectionFactory;

final class PostgreSQLPdoBackend
{
    public static function dependencies(): array
    {
        return [
            ConnectionFactory::class => PdoConnectionFactory::class,
            ConnectionChecker::class => PdoConnectionChecker::class,
            HyperfConnectionChecker::class => PdoConnectionChecker::class,
            DatabaseConnectionFactory::class => SingleAttemptConnectionFactory::class,
            DbPool::class => SingleAttemptPool::class,
            'db.connector.pgsql' => SingleAttemptConnector::class,
        ];
    }
}
