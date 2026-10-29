<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Hyperf\DB\PgSQL\PgSQLPool;

final class PostgreSQLSessionRecovery
{
    public static function dependencies(): array
    {
        return [
            PgSQLPool::class => DiagnosticPgSQLPool::class,
        ];
    }
}
