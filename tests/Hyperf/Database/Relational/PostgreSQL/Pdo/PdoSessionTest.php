<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
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
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnection;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnectionFactory;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PostgreSQLPdoBackend;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptPool;
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
