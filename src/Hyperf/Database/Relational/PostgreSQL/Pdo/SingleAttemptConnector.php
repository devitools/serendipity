<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Database\PgSQL\Connectors\PostgresConnector;
use PDO;

use function Constructo\Cast\stringify;

class SingleAttemptConnector extends PostgresConnector
{
    public function getOptions(array $config)
    {
        return array_replace(parent::getOptions($config), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
        ]);
    }

    public function createConnection($dsn, array $config, array $options)
    {
        return $this->createPdoConnection(
            $dsn,
            stringify($config['username'] ?? ''),
            stringify($config['password'] ?? ''),
            $options
        );
    }
}
