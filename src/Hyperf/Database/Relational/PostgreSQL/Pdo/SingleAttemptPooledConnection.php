<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\DbConnection\Connection;
use Hyperf\Database\ConnectionInterface;

class SingleAttemptPooledConnection extends Connection
{
    public function getConnection(): ConnectionInterface
    {
        return $this->getActiveConnection();
    }

    public function release(): void
    {
        if ($this->connection instanceof SingleAttemptPostgreSQLConnection && $this->connection->isInvalid()) {
            $this->lastUseTime = 0.0;
            $this->connection->rollBack(0);
            $this->connection->disconnect();
        }

        parent::release();
    }
}
