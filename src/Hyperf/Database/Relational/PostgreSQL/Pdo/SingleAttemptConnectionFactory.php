<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Database\Connectors\ConnectionFactory;
use InvalidArgumentException;

use function Constructo\Cast\arrayify;
use function Constructo\Cast\stringify;

class SingleAttemptConnectionFactory extends ConnectionFactory
{
    protected function parseConfig(array $config, $name): array
    {
        if (($config['driver'] ?? null) === 'pgsql') {
            $this->assertSingleHost($config);
            foreach (['read', 'write'] as $role) {
                $this->assertSingleHost($this->singleRoleConfig(arrayify($config[$role] ?? [])));
            }
        }

        return parent::parseConfig($config, $name);
    }

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

    private function assertSingleHost(array $config): void
    {
        $endpoints = 0;
        foreach ((array) ($config['host'] ?? []) as $host) {
            $endpoints += count(explode(',', stringify($host)));
        }
        if ($endpoints > 1) {
            throw new InvalidArgumentException('The PDO PostgreSQL backend requires a single host per role.');
        }
    }

    private function singleRoleConfig(array $config): array
    {
        if (! isset($config[0])) {
            return $config;
        }
        if (count($config) > 1) {
            throw new InvalidArgumentException('The PDO PostgreSQL backend requires a single host per role.');
        }

        return arrayify($config[0]);
    }
}
