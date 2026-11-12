<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionResolverInterface;
use InvalidArgumentException;
use Serendipity\Infrastructure\Database\Relational\Connection;
use Serendipity\Infrastructure\Database\Relational\ConnectionFactory;

final readonly class PdoConnectionFactory implements ConnectionFactory
{
    public function __construct(
        private ConnectionResolverInterface $resolver,
        private ConfigInterface $config,
    ) {
    }

    public function make(string $connection): Connection
    {
        if ($this->config->get('databases.' . $connection . '.driver') !== 'pgsql') {
            throw new InvalidArgumentException(
                'The PDO backend requires a named pgsql connection in databases configuration.'
            );
        }

        return new PdoConnection($connection, $this->resolver);
    }
}
