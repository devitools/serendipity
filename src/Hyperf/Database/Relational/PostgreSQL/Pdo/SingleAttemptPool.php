<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\DbConnection\Pool\DbPool;

class SingleAttemptPool extends DbPool
{
    protected function createConnection(): ConnectionInterface
    {
        if ($this->config['driver'] === 'pgsql') {
            return new SingleAttemptPooledConnection($this->container, $this, $this->config);
        }

        return parent::createConnection();
    }
}
