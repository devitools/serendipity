<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DB\PgSQL\PgSQLPool;
use Hyperf\DB\Pool\MySQLPool;
use Hyperf\DB\Pool\PoolFactory;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use PHPUnit\Framework\TestCase;
use Serendipity\ConfigProvider;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\DiagnosticPgSQLPool;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PostgreSQLSessionRecovery;

class PostgreSQLSessionRecoveryTest extends TestCase
{
    public function testDefaultConfigurationDoesNotEnableRecovery(): void
    {
        $configuration = (new ConfigProvider())();

        $this->assertArrayNotHasKey(PgSQLPool::class, $configuration['dependencies']);
        $this->assertSame([PgSQLPool::class => DiagnosticPgSQLPool::class], PostgreSQLSessionRecovery::dependencies());
    }

    public function testPoolFactoryKeepsNativePostgresWhenModuleIsNotEnabled(): void
    {
        $original = ApplicationContext::getContainer();
        $container = new Container(new DefinitionSource([]));
        $container->set(ConfigInterface::class, new Config(['db' => ['postgres' => ['driver' => 'pgsql']]]));
        ApplicationContext::setContainer($container);
        try {
            $pool = $container->get(PoolFactory::class)->getPool('postgres');

            $this->assertSame(PgSQLPool::class, $pool::class);
        } finally {
            ApplicationContext::setContainer($original);
        }
    }

    public function testEnablingRecoveryLeavesMysqlPoolUnchanged(): void
    {
        $original = ApplicationContext::getContainer();
        $container = new Container(new DefinitionSource(PostgreSQLSessionRecovery::dependencies()));
        $container->set(ConfigInterface::class, new Config(['db' => ['mysql' => ['driver' => 'mysql']]]));
        ApplicationContext::setContainer($container);
        try {
            $pool = $container->get(PoolFactory::class)->getPool('mysql');

            $this->assertSame(MySQLPool::class, $pool::class);
        } finally {
            ApplicationContext::setContainer($original);
        }
    }
}
