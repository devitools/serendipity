<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Database\Exception\QueryException;
use Hyperf\DbConnection\ConfigProvider;
use Hyperf\DbConnection\Pool\DbPool;
use Hyperf\DbConnection\Pool\PoolFactory;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\HyperfConnection;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionChecker;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnection;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnectionChecker;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnectionFactory;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PostgreSQLPdoBackend;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptPool;
use Serendipity\Hyperf\Middleware\ConnectionCheckerMiddleware;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;

class PdoSessionTest extends TestCase
{
    private ContainerInterface $original;

    private Container $container;

    private DbPool $pool;

    private PdoConnection $database;

    protected function setUp(): void
    {
        parent::setUp();
        $this->original = ApplicationContext::getContainer();
        $settings = $this->original->get(ConfigInterface::class)->get('databases.postgres');
        $settings['pool']['max_connections'] = 1;
        $settings['pool']['wait_timeout'] = 0.5;
        $dependencies = [
            ...(new ConfigProvider())()['dependencies'],
            ...(new \Hyperf\Database\PgSQL\ConfigProvider())()['dependencies'],
            ...PostgreSQLPdoBackend::dependencies(),
        ];
        $this->container = new Container(new DefinitionSource($dependencies));
        $this->container->set(ConfigInterface::class, new Config(['databases' => ['pdo_test' => $settings]]));
        $this->container->set(StdoutLoggerInterface::class, $this->original->get(StdoutLoggerInterface::class));
        ApplicationContext::setContainer($this->container);
        $this->pool = $this->container->get(PoolFactory::class)->getPool('pdo_test');
        $this->database = $this->container->get(PdoConnectionFactory::class)->make('pdo_test');
    }

    protected function tearDown(): void
    {
        try {
            while ($this->pool->getConnectionsInChannel() > 0) {
                $this->pool->flushOne(true);
            }
        } finally {
            ApplicationContext::setContainer($this->original);
            parent::tearDown();
        }
    }

    public function testActualPdoBackendPreservesBindingsShapesSchemaAndAffectedRows(): void
    {
        $this->assertInstanceOf(SingleAttemptPool::class, $this->pool);
        $this->borrow(function (): void {
            $this->database->run(function (PDO $pdo): void {
                $this->assertSame('pgsql', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
            });
            $this->assertSame([['value' => 'example']], $this->database->query('SELECT ?::text AS value', ['example']));
            $this->assertSame('public', $this->database->fetch('SELECT current_schema() AS value')->get('value'));
            $this->assertSame([], $this->database->fetch('SELECT 1 WHERE FALSE')->toArray());
            $this->database->execute('CREATE TEMP TABLE pdo_contract (id integer PRIMARY KEY, active boolean)');
            $this->assertSame(1, $this->database->insert('INSERT INTO pdo_contract VALUES (?, ?)', [1, true]));
            $this->assertSame(1, $this->database->execute('UPDATE pdo_contract SET active = ? WHERE id = ?', [false, 1]));
            $this->assertSame([['id' => 1, 'active' => false]], $this->database->query('SELECT * FROM pdo_contract'));
        });
        $this->assertSame(1, $this->pool->getCurrentConnections());
        $this->assertSame(1, $this->pool->getConnectionsInChannel());
    }

    public function testNestedTransactionsRollbackToSavepointAndCommitOuterWork(): void
    {
        $this->borrow(function (): void {
            $this->database->execute('CREATE TEMP TABLE pdo_nested (id integer PRIMARY KEY)');
            $this->database->beginTransaction();
            $this->database->execute('INSERT INTO pdo_nested VALUES (1)');
            $this->database->beginTransaction();
            $this->database->execute('INSERT INTO pdo_nested VALUES (2)');
            $this->database->rollback();
            $this->database->commit();
            $this->assertSame([['id' => 1]], $this->database->query('SELECT id FROM pdo_nested'));
        });
    }

    public function testPdoQueriesYieldToOtherCoroutines(): void
    {
        $this->borrow(function (): void {
            $ticks = 0;
            $timer = Coroutine::create(static function () use (&$ticks): void {
                Coroutine::sleep(0.01);
                ++$ticks;
            });
            try {
                $this->database->query('SELECT pg_sleep(0.05)');
                $this->assertSame(1, $ticks, 'PDO PostgreSQL must not block the event loop');
            } finally {
                Coroutine::join([$timer], 1);
            }
        });
    }

    public function testNativeTransactionIsDiscardedWhenCallbackThrows(): void
    {
        $failure = new RuntimeException('application failure after native BEGIN');
        $native = null;
        $pid = $this->borrow(function () use ($failure, &$native): int {
            $pid = $this->pid();
            try {
                $this->database->run(static function (PDO $pdo) use ($failure, &$native): void {
                    $native = $pdo;
                    $pdo->beginTransaction();
                    $pdo->exec('CREATE TEMP TABLE pdo_native_uncommitted (id integer)');
                    throw $failure;
                });
                $this->fail('Expected callback failure');
            } catch (RuntimeException $exception) {
                $this->assertSame($failure, $exception);
            }
            return $pid;
        });

        $this->assertInstanceOf(PDO::class, $native);
        $this->assertFalse($native->inTransaction());
        $this->borrow(function () use ($pid): void {
            $this->assertNotSame($pid, $this->pid());
            $this->database->run(function (PDO $pdo): void {
                $this->assertFalse($pdo->inTransaction());
            });
            $this->assertNull($this->database->fetch("SELECT to_regclass('pg_temp.pdo_native_uncommitted') AS value")->get('value'));
            $this->database->beginTransaction();
            $this->database->query('SELECT 1');
            $this->database->commit();
        });
        $this->assertSame(1, $this->pool->getCurrentConnections());
        $this->assertSame(1, $this->pool->getConnectionsInChannel());
    }

    public function testDeferredConstraintCommitFailureAllowsAnotherTransaction(): void
    {
        $pid = $this->borrow(function (): int {
            $pid = $this->pid();
            $this->database->execute('CREATE TEMP TABLE pdo_deferred (id integer UNIQUE DEFERRABLE INITIALLY DEFERRED)');
            $this->database->beginTransaction();
            $this->database->execute('INSERT INTO pdo_deferred VALUES (1), (1)');
            try {
                $this->database->commit();
                $this->fail('Expected deferred constraint failure');
            } catch (\PDOException $exception) {
                $this->assertSame('23505', $exception->errorInfo[0]);
            }
            $this->database->beginTransaction();
            $this->database->execute('INSERT INTO pdo_deferred VALUES (2)');
            $this->database->commit();
            $this->assertSame([['id' => 2]], $this->database->query('SELECT id FROM pdo_deferred'));
            return $pid;
        });
        $this->assertSame($pid, $this->borrow(fn () => $this->pid()));
    }

    public function testOptInDoesNotChangeMysqlPoolSessions(): void
    {
        $this->container->get(ConfigInterface::class)->set('databases.mysql', [
            'driver' => 'mysql', 'database' => 'unused', 'host' => 'unused',
        ]);
        $pool = $this->container->get(PoolFactory::class)->getPool('mysql');
        $connection = $pool->get();
        try {
            $this->assertSame(\Hyperf\DbConnection\Connection::class, $connection::class);
        } finally {
            $connection->release();
            $pool->flushOne(true);
        }
    }

    public function testDistinctNamedDatabasesKeepIndependentPoolsAndSessions(): void
    {
        $suffix = bin2hex(random_bytes(5));
        $names = ['pdo_named_' . $suffix . '_first', 'pdo_named_' . $suffix . '_second'];
        $admin = $this->admin();
        $pools = [];
        $connections = [];
        try {
            foreach ($names as $name) {
                $admin->exec('CREATE DATABASE ' . $name);
                $settings = $this->container->get(ConfigInterface::class)->get('databases.pdo_test');
                $settings['database'] = $name;
                $settings['host'] = [$settings['host']];
                $this->container->get(ConfigInterface::class)->set('databases.' . $name, $settings);
                $pools[$name] = $this->container->get(PoolFactory::class)->getPool($name);
                $connections[$name] = $this->container->get(PdoConnectionFactory::class)->make($name);
            }

            $this->borrow(function () use ($connections, $pools, $names): void {
                $pids = [];
                foreach ($connections as $name => $connection) {
                    $row = $connection->fetch('SELECT current_database() AS name, pg_backend_pid() AS pid');
                    $this->assertSame($name, $row->get('name'));
                    $pids[] = $row->get('pid');
                    $this->assertSame(1, $pools[$name]->getCurrentConnections());
                    $this->assertSame(0, $pools[$name]->getConnectionsInChannel());
                }
                $this->assertNotSame($pids[0], $pids[1]);
                $this->assertNotSame($pools[$names[0]], $pools[$names[1]]);
            });

            foreach ($pools as $pool) {
                $this->assertSame(1, $pool->getConnectionsInChannel());
            }
        } finally {
            foreach ($pools as $pool) {
                while ($pool->getConnectionsInChannel() > 0) {
                    $pool->flushOne(true);
                }
            }
            foreach ($names as $name) {
                $admin->exec('DROP DATABASE IF EXISTS ' . $name);
            }
        }
    }

    public function testMiddlewareUsesPdoAndReleasesProbeLeaseBeforeCallingHandler(): void
    {
        $settings = $this->container->get(ConfigInterface::class)->get('databases.pdo_test');
        $this->container->get(ConfigInterface::class)->set('databases.default', $settings);
        $pool = $this->container->get(PoolFactory::class)->getPool('default');
        $native = $this->createMock(HyperfConnection::class);
        $native->expects($this->never())->method('run');
        $this->container->set(HyperfConnection::class, $native);
        $this->assertInstanceOf(PdoConnectionChecker::class, $this->container->get(HyperfConnectionChecker::class));
        $this->assertInstanceOf(
            PdoConnectionChecker::class,
            $this->container->get(\Serendipity\Infrastructure\Database\Relational\ConnectionChecker::class)
        );
        $middleware = new ConnectionCheckerMiddleware($this->container);
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)
            ->willReturnCallback(function () use ($pool, $response): ResponseInterface {
                $this->assertSame(1, $pool->getCurrentConnections());
                $this->assertSame(1, $pool->getConnectionsInChannel());
                Coroutine::sleep(0.01);
                $this->assertSame(1, $pool->getConnectionsInChannel());
                return $response;
            });

        try {
            $this->borrow(function () use ($middleware, $request, $handler, $response): void {
                Context::set('database.connection.default', $this->createMock(\Hyperf\Database\ConnectionInterface::class));
                $this->assertSame($response, $middleware->process($request, $handler));
            });
            $this->assertSame(1, $pool->getConnectionsInChannel());
        } finally {
            while ($pool->getConnectionsInChannel() > 0) {
                $pool->flushOne(true);
            }
        }
    }

    public function testFailedMiddlewareProbeReturnsLeaseAndStillCallsHandler(): void
    {
        $settings = $this->container->get(ConfigInterface::class)->get('databases.pdo_test');
        $settings['schema'] = 'invalid"schema';
        $this->container->get(ConfigInterface::class)->set('databases.default', $settings);
        $pool = $this->container->get(PoolFactory::class)->getPool('default');
        $middleware = new ConnectionCheckerMiddleware($this->container);
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)
            ->willReturnCallback(function () use ($pool, $response): ResponseInterface {
                $this->assertSame(1, $pool->getCurrentConnections());
                $this->assertSame(1, $pool->getConnectionsInChannel());
                return $response;
            });

        try {
            $this->borrow(function () use ($middleware, $request, $handler, $response): void {
                $this->assertSame($response, $middleware->process($request, $handler));
            });
        } finally {
            while ($pool->getConnectionsInChannel() > 0) {
                $pool->flushOne(true);
            }
        }
    }

    public function testReleaseDoesNotResolveUntouchedPdoOrRepeatFailedInitialization(): void
    {
        $settings = $this->container->get(ConfigInterface::class)->get('databases.pdo_test');
        $settings['schema'] = 'invalid"schema';
        $this->container->get(ConfigInterface::class)->set('databases.lazy_pdo', $settings);
        $pool = $this->container->get(PoolFactory::class)->getPool('lazy_pdo');
        $connection = $pool->get();
        $connection->release();
        $this->assertSame(1, $pool->getConnectionsInChannel());
        $connection = $pool->get();
        try {
            $connection->select('SELECT 1');
            $this->fail('Expected initialization syntax error');
        } catch (\PDOException $exception) {
            $this->assertSame('42601', $exception->errorInfo[0]);
        } finally {
            $connection->release();
        }
        $this->assertSame(1, $pool->getConnectionsInChannel());
        $pool->flushOne(true);
    }

    public function testSqlErrorAndServerTimeoutPreserveSessionAfterNormalRollback(): void
    {
        $pid = $this->borrow(function (): int {
            $pid = $this->pid();
            $this->database->beginTransaction();
            try {
                $this->database->query('SELECT FROM');
                $this->fail('Expected syntax error');
            } catch (QueryException) {
                $this->database->rollback();
            }
            $this->database->execute("SET statement_timeout = '10ms'");
            try {
                $this->database->query('SELECT pg_sleep(0.1)');
                $this->fail('Expected server timeout');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('statement timeout', $exception->getMessage());
            } finally {
                $this->database->execute('RESET statement_timeout');
            }
            $this->assertSame($pid, $this->pid());
            return $pid;
        });
        $this->assertSame($pid, $this->borrow(fn () => $this->pid()));
    }

    public function testLostSessionRecoversInNextCoroutineWithoutGrowingPool(): void
    {
        for ($cycle = 0; $cycle < 3; ++$cycle) {
            $pid = $this->borrow(function (): int {
                $pid = $this->pid();
                $this->terminate($pid);
                try {
                    $this->database->query('SELECT 1');
                    $this->fail('The lost operation must fail without replay');
                } catch (QueryException) {
                    $this->addToAssertionCount(1);
                }
                return $pid;
            });
            $this->assertNotSame($pid, $this->borrow(fn () => $this->pid()));
            $this->assertSame(1, $this->pool->getCurrentConnections());
            $this->assertSame(1, $this->pool->getConnectionsInChannel());
        }
    }

    public function testTransactionCannotContinueOnAnotherSessionAfterConnectionLoss(): void
    {
        $old = $this->borrow(function (): int {
            $pid = $this->pid();
            $this->database->beginTransaction();
            $this->terminate($pid);
            try {
                $this->database->query('SELECT 1');
                $this->fail('Expected connection loss');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
            try {
                $this->database->commit();
                $this->fail('Must not commit on a replacement session');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('operation was not executed', $exception->getMessage());
            }
            $this->database->rollback();
            return $pid;
        });
        $this->assertNotSame($old, $this->borrow(fn () => $this->pid()));
    }

    public function testNativePdoCallbackConnectionLossAlsoRecoversOnNextAcquisition(): void
    {
        $pid = $this->borrow(function (): int {
            $pid = $this->pid();
            $this->terminate($pid);
            try {
                $this->database->run(static fn (PDO $pdo) => $pdo->query('SELECT 1'));
                $this->fail('Expected native PDO connection loss');
            } catch (\PDOException) {
                $this->addToAssertionCount(1);
            }
            return $pid;
        });
        $this->assertNotSame($pid, $this->borrow(fn () => $this->pid()));
        $this->assertSame(1, $this->pool->getConnectionsInChannel());
    }

    public function testFailedStatementWithNonTransactionalSideEffectIsExecutedExactlyOnce(): void
    {
        $sequence = 'pdo_once_' . bin2hex(random_bytes(5));
        $admin = $this->admin();
        $admin->exec('CREATE SEQUENCE ' . $sequence);
        try {
            $this->borrow(function () use ($sequence): void {
                try {
                    $this->database->execute("DO \$\$ BEGIN PERFORM nextval('{$sequence}'); RAISE EXCEPTION 'server closed the connection unexpectedly' USING ERRCODE='08006'; END \$\$");
                    $this->fail('Expected simulated transport error');
                } catch (QueryException) {
                    $this->addToAssertionCount(1);
                }
            });
            $this->assertSame(1, (int) $admin->query('SELECT last_value FROM ' . $sequence)->fetchColumn());
        } finally {
            $admin->exec('DROP SEQUENCE ' . $sequence);
        }
    }

    public function testNativeCallbackErrorsAndCoroutineConcurrencyDoNotLeakConnections(): void
    {
        $failure = new RuntimeException('application failure');
        $this->borrow(function () use ($failure): void {
            $pid = $this->pid();
            try {
                $this->database->run(static fn () => throw $failure);
                $this->fail('Expected callback failure');
            } catch (RuntimeException $exception) {
                $this->assertSame($failure, $exception);
            }
            $this->assertSame($pid, $this->pid());
        });

        $results = new Channel(4);
        $ids = [];
        for ($index = 0; $index < 4; ++$index) {
            $ids[] = Coroutine::create(function () use ($results): void {
                try {
                    $results->push($this->database->query('SELECT pg_sleep(0.01), 42 AS value')[0]['value']);
                } catch (Throwable $exception) {
                    $results->push($exception);
                }
            });
        }
        $this->assertTrue(Coroutine::join($ids, 3));
        for ($index = 0; $index < 4; ++$index) {
            $this->assertSame(42, $results->pop(0.1));
        }
        $this->assertSame(1, $this->pool->getCurrentConnections());
        $this->assertSame(1, $this->pool->getConnectionsInChannel());
    }

    private function borrow(callable $operation): mixed
    {
        $result = new Channel(1);
        $id = Coroutine::create(static function () use ($operation, $result): void {
            try {
                $result->push($operation());
            } catch (Throwable $exception) {
                $result->push($exception);
            }
        });
        $this->assertTrue(Coroutine::join([$id], 3));
        $value = $result->pop(0.1);
        if ($value instanceof Throwable) {
            throw $value;
        }
        return $value;
    }

    private function pid(): int
    {
        return (int) $this->database->query('SELECT pg_backend_pid() AS pid')[0]['pid'];
    }

    private function terminate(int $pid): void
    {
        $this->assertTrue((bool) $this->admin()->query('SELECT pg_terminate_backend(' . $pid . ')')->fetchColumn());
    }

    private function admin(): PDO
    {
        $config = $this->original->get(ConfigInterface::class)->get('databases.postgres');
        return new PDO(sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['database']), $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
}
