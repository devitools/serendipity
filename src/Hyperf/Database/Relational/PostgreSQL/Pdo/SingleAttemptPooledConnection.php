<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\DbConnection\Connection;
use Hyperf\Database\ConnectionInterface;
use Throwable;

class SingleAttemptPooledConnection extends Connection
{
    public function getConnection(): ConnectionInterface
    {
        return $this->getActiveConnection();
    }

    public function release(): void
    {
        try {
            if (
                $this->connection instanceof SingleAttemptPostgreSQLConnection
                && $this->connection->requiresDiscard()
            ) {
                $this->lastUseTime = 0.0;
                $this->discardConnection($this->connection);
            }
        } catch (Throwable $exception) {
            $this->lastUseTime = 0.0;
            $this->connection->disconnect();
            $this->logger->error('PostgreSQL session cleanup failed: ' . $exception->getMessage());
        } finally {
            parent::release();
        }
    }

    private function discardConnection(SingleAttemptPostgreSQLConnection $connection): void
    {
        try {
            if (! $connection->isInvalid()) {
                $connection->getPdo()->rollBack();
            }
        } finally {
            $connection->disconnect();
        }
    }
}
