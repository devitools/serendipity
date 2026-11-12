<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Database\Connectors\ConnectionFactory;

class SingleAttemptConnectionFactory extends ConnectionFactory
{
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($driver === 'pgsql') {
            return new SingleAttemptPostgreSQLConnection(
                $connection,
                $database,
                $prefix,
                $config,
                $this->container->get(PdoFailureClassifier::class)
            );
        }

        return parent::createConnection($driver, $connection, $database, $prefix, $config);
    }
}
