<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Config\Config;
use Hyperf\Contract\PoolOptionInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\MySqlConnection;
use Hyperf\DbConnection\Pool\DbPool;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Serendipity\ConfigProvider;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionChecker;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnection;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnectionChecker;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnectionFactory;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoFailureClassifier;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PostgreSQLPdoBackend;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptConnectionFactory;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptConnector;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptPool;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptPooledConnection;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptPostgreSQLConnection;
use Serendipity\Infrastructure\Database\Relational\ConnectionFactory;
use Swoole\Runtime;

class PdoBackendTest extends TestCase
{
    public function testModuleIsOptInAndDoesNotChangeDefaultDependencies(): void
    {
        $defaults = (new ConfigProvider())()['dependencies'];
        $this->assertArrayNotHasKey(ConnectionFactory::class, $defaults);
        $this->assertArrayNotHasKey(DbPool::class, $defaults);
        $this->assertArrayNotHasKey(HyperfConnectionChecker::class, $defaults);
        $dependencies = PostgreSQLPdoBackend::dependencies();
        $this->assertSame(PdoConnectionFactory::class, $dependencies[ConnectionFactory::class]);
        $this->assertSame(SingleAttemptPool::class, $dependencies[DbPool::class]);
        $this->assertSame(PdoConnectionChecker::class, $dependencies[HyperfConnectionChecker::class]);
        $this->assertSame(
            PdoConnectionChecker::class,
            $dependencies[\Serendipity\Infrastructure\Database\Relational\ConnectionChecker::class]
        );
    }

    public function testFactoryValidatesNamesWithoutOpeningOrRetainingAConnection(): void
    {
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->expects($this->never())->method('connection');
        $factory = new PdoConnectionFactory($resolver, new Config(['databases' => ['primary' => ['driver' => 'pgsql']]]));

        $connection = $factory->make('primary');
        $this->assertInstanceOf(PdoConnection::class, $connection);
        $this->assertSame('primary', $connection->connection);
        $this->expectException(InvalidArgumentException::class);
        $factory->make('missing');
    }

    public function testAdapterRejectsDisabledPdoHookBeforeAcquiringPool(): void
    {
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->expects($this->never())->method('connection');
        $connection = new PdoConnection('primary', $resolver);
        $flags = Runtime::getHookFlags();
        Runtime::setHookFlags($flags & ~SWOOLE_HOOK_PDO_PGSQL);
        try {
            $connection->query('SELECT 1');
            $this->fail('Blocking PDO must not be silently enabled');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('SWOOLE_HOOK_PDO_PGSQL', $exception->getMessage());
        } finally {
            Runtime::setHookFlags($flags);
        }
    }

    public function testAdapterRejectsNonPooledResolver(): void
    {
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->method('connection')->willReturn(new MySqlConnection($this->createMock(PDO::class)));
        $connection = new PdoConnection('primary', $resolver);
        $this->expectException(LogicException::class);
        $connection->query('SELECT 1');
    }

    public function testMysqlConnectionCreationIsNotChangedByOptIn(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new SingleAttemptConnectionFactory($container);
        $connection = $factory->make(['driver' => 'mysql', 'host' => 'unused', 'database' => 'unused']);
        $this->assertSame(MySqlConnection::class, $connection::class);
    }

    public function testConnectorDoesNotRetryFailedConnectAndEnforcesSafeOptions(): void
    {
        $connector = $this->getMockBuilder(SingleAttemptConnector::class)->onlyMethods(['createPdoConnection'])->getMock();
        $failure = new PDOException('server closed the connection unexpectedly');
        $connector->expects($this->once())->method('createPdoConnection')->willThrowException($failure);
        $options = $connector->getOptions(['options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT, PDO::ATTR_PERSISTENT => true]]);
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $options[PDO::ATTR_ERRMODE]);
        $this->assertFalse($options[PDO::ATTR_PERSISTENT]);

        $this->expectExceptionObject($failure);
        $connector->createConnection('pgsql:host=unused', [], $options);
    }

    public function testClassifierIgnoresApplicationExceptionsAndUnrelatedGenericPdoErrors(): void
    {
        $classifier = new PdoFailureClassifier();
        $this->assertFalse($classifier->isConnectionFailure(new \RuntimeException('connection lost')));
        $this->assertFalse($classifier->isConnectionFailure(new PDOException('unrelated generic error')));
    }

    public function testFailedAcquisitionIsNotRepeated(): void
    {
        $connection = $this->getMockBuilder(SingleAttemptPooledConnection::class)
            ->disableOriginalConstructor()->onlyMethods(['getActiveConnection'])->getMock();
        $failure = new \RuntimeException('initialization failed');
        $connection->expects($this->once())->method('getActiveConnection')->willThrowException($failure);

        $this->expectExceptionObject($failure);
        $connection->getConnection();
    }

    public function testReleaseReturnsManagedConnectionEvenWhenNativeCleanupFails(): void
    {
        foreach (['inTransaction', 'rollBack'] as $operation) {
            $pdo = $this->createMock(PDO::class);
            $pdo->method('inTransaction')->willReturn(true);
            $pdo->expects($this->once())->method($operation)->willThrowException(new PDOException('cleanup failure'));
            $database = new SingleAttemptPostgreSQLConnection($pdo);
            $factory = $this->createMock(SingleAttemptConnectionFactory::class);
            $factory->method('make')->willReturn($database);
            $logger = $this->createMock(StdoutLoggerInterface::class);
            $logger->expects($this->once())->method('error');
            $container = $this->createMock(ContainerInterface::class);
            $container->method('get')->willReturnMap([
                [\Hyperf\Database\Connectors\ConnectionFactory::class, $factory],
                [StdoutLoggerInterface::class, $logger],
            ]);
            $pool = $this->createMock(DbPool::class);
            $option = $this->createMock(PoolOptionInterface::class);
            $option->method('getEvents')->willReturn([]);
            $pool->method('getOption')->willReturn($option);
            $connection = new SingleAttemptPooledConnection($container, $pool, []);
            $pool->expects($this->once())->method('release')->with($connection);

            $connection->release();

            $this->assertNull($database->getPdo());
            $this->assertSame(0.0, $connection->getLastUseTime());
        }
    }
}
