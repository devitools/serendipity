<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\DB\PgSQL\PgSQLPool;

use function Hyperf\Support\make;

class DiagnosticPgSQLPool extends PgSQLPool
{
    protected function createConnection(): ConnectionInterface
    {
        return make(DiagnosticPgSQLConnection::class, [
            'container' => $this->container,
            'pool' => $this,
            'config' => $this->config,
            'session' => make(PgSQLSession::class, ['poolName' => $this->getName()]),
        ]);
    }
}
