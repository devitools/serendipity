<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use Exception;
use Hyperf\Context\ApplicationContext;
use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DB\DB;
use Hyperf\DB\Exception\QueryException;
use Hyperf\DB\Pool\PoolFactory;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Tracer\TracerContext;
use OpenTracing\NoopTracer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\DiagnosticPgSQLConnection;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\DiagnosticPgSQLPool;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PostgreSQLSessionRecovery;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Throwable;
use WeakReference;

use function bin2hex;
use function random_bytes;

class DiagnosticPgSQLPoolTest extends TestCase
{
    private ContainerInterface $originalContainer;

    private array $pools = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalContainer = ApplicationContext::getContainer();
        $container = new Container(new DefinitionSource(PostgreSQLSessionRecovery::dependencies()));
        $container->set(ConfigInterface::class, $this->originalContainer->get(ConfigInterface::class));
        $container->set(LoggerInterface::class, new NullLogger());
        $container->set(StdoutLoggerInterface::class, $this->originalContainer->get(StdoutLoggerInterface::class));
        $container->set(SwitchManager::class, $this->originalContainer->get(SwitchManager::class));
        ApplicationContext::setContainer($container);
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->pools as $pool) {
                while ($pool->getConnectionsInChannel() > 0) {
                    $pool->flushOne(true);
                }
            }
        } finally {
            $this->pools = [];
            ApplicationContext::setContainer($this->originalContainer);
            parent::tearDown();
        }
    }

    public function testPoolFactoryCreatesDiagnosticConnectionsAndPreservesBindings(): void
    {
        [$database, $pool] = $this->database();

        $this->assertInstanceOf(DiagnosticPgSQLPool::class, $pool);
        $this->assertTrue($database->run(fn () => $this instanceof DiagnosticPgSQLConnection));
        $this->assertSame([['value' => 'example']], $database->query('SELECT ?::text AS value', ['example']));
        $this->assertSame(1, $pool->getCurrentConnections());
        $this->assertSame(1, $pool->getConnectionsInChannel());
    }

    public function testTimeoutRecoversOnNextAcquisitionWithAndWithoutTracing(): void
    {
        $switch = ApplicationContext::getContainer()->get(SwitchManager::class);
        $enabled = $switch->isEnable('db');
        $tracer = Context::get(TracerContext::TRACER);
        $root = TracerContext::getRoot();
        TracerContext::setTracer(new NoopTracer());
        TracerContext::setRoot((new NoopTracer())->startSpan('session-recovery'));
        try {
            foreach ([false, true] as $tracing) {
                $switch->apply(['db' => $tracing]);
                [$database, $pool] = $this->database();
                $pid = $this->pid($database);
                for ($cycle = 0; $cycle < 3; ++$cycle) {
                    $this->expectTimeout(fn () => $database->query('SELECT pg_sleep(0.15)'));
                    $nextPid = $this->pid($database);
                    $this->assertNotSame($pid, $nextPid);
                    $this->assertSame(1, $pool->getCurrentConnections());
                    $this->assertSame(1, $pool->getConnectionsInChannel());
                    $pid = $nextPid;
                }
            }
        } finally {
            $switch->apply(['db' => $enabled]);
            Context::set(TracerContext::TRACER, $tracer);
            Context::set(TracerContext::ROOT, $root);
        }
    }

    public function testSyntaxConstraintAndServerCancellationPreserveHealthySession(): void
    {
        [$database] = $this->database();
        $pid = $this->pid($database);
        $database->execute('CREATE TEMP TABLE session_recovery_unique (id integer PRIMARY KEY)');
        $database->execute('INSERT INTO session_recovery_unique VALUES (1)');
        foreach (['SELECT FROM', 'INSERT INTO session_recovery_unique VALUES (1)'] as $query) {
            try {
                $database->query($query);
                $this->fail('Expected SQL error');
            } catch (QueryException) {
                $this->assertSame($pid, $this->pid($database));
            }
        }
        $database->execute("SET statement_timeout = '20ms'");
        try {
            $database->query('SELECT pg_sleep(0.15)');
            $this->fail('Expected server cancellation');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('statement timeout', $exception->getMessage());
        } finally {
            $database->execute('RESET statement_timeout');
        }
        $this->assertSame($pid, $this->pid($database));
    }

    public function testSqlMessagesContainingTransportPatternsPreserveSavepointRecovery(): void
    {
        [$database, $pool] = $this->database();
        $pid = $this->pid($database);
        $connection = $pool->get()->getConnection();
        try {
            $connection->execute('CREATE TEMP TABLE recovery_preserved_writes (value integer)');
            $connection->beginTransaction();
            $connection->execute('INSERT INTO recovery_preserved_writes VALUES (1)');
            foreach (['missing_onerror_review_probe', 'missing_broken pipe_review_probe'] as $table) {
                $connection->beginTransaction();
                try {
                    $connection->query('SELECT * FROM "' . $table . '"');
                    $this->fail('Expected a missing relation error');
                } catch (QueryException $exception) {
                    $this->assertStringContainsString($table, $exception->getMessage());
                    $this->assertTrue($connection->check());
                    $this->assertSame(2, $connection->transactionLevel());
                }
                $connection->rollBack();
                $this->assertSame(1, $connection->transactionLevel());
                $this->assertSame($pid, (int) $connection->query('SELECT pg_backend_pid() AS pid')[0]['pid']);
            }
            $connection->execute('INSERT INTO recovery_preserved_writes VALUES (2)');
            $connection->commit();
        } finally {
            $connection->release();
        }
        $this->assertSame([['value' => 1], ['value' => 2]], $database->query('SELECT value FROM recovery_preserved_writes ORDER BY value'));
        $this->assertSame($pid, $this->pid($database));
        $this->assertSame(1, $pool->getCurrentConnections());
        $this->assertSame(1, $pool->getConnectionsInChannel());
    }

    public function testNativeTimeoutOverridesStaleSqlDiagnosticsWithoutReplayingCallback(): void
    {
        [$database, $pool] = $this->database();
        $pid = $this->pid($database);
        $attempts = 0;
        $diagnostics = [];

        $callback = function ($driver) use (&$attempts, &$diagnostics): void {
            ++$attempts;
            $driver->resultDiag = ['sqlstate' => '42P01', 'message_primary' => 'old SQL error'];
            if ($driver->query('SELECT pg_sleep(0.15)') === false) {
                $diagnostics = [
                    'sqlstate' => $driver->resultDiag['sqlstate'],
                    'code' => $driver->errCode,
                    'error' => $driver->error,
                ];
                throw new QueryException('Native callback timed out');
            }
        };
        $this->expectTimeout(fn () => $database->run($callback));

        $this->assertSame(1, $attempts);
        $this->assertSame(['sqlstate' => '42P01', 'code' => 0, 'error' => 'Coroutine timedout'], $diagnostics);
        $this->assertNotSame($pid, $this->pid($database));
        $this->assertSame(1, $pool->getCurrentConnections());
        $this->assertSame(1, $pool->getConnectionsInChannel());
    }

    public function testHealthyTransactionCanRollbackAfterSqlError(): void
    {
        [$database, $pool] = $this->database();
        $pid = $this->pid($database);
        $connection = $pool->get()->getConnection();
        try {
            $connection->beginTransaction();
            try {
                $connection->query('SELECT FROM');
                $this->fail('Expected SQL syntax error');
            } catch (QueryException) {
                $this->assertTrue($connection->check());
            }
            $connection->rollBack();
            $this->assertSame(0, $connection->transactionLevel());
        } finally {
            $connection->release();
        }
        $this->assertSame($pid, $this->pid($database));
    }

    public function testInvalidTransactionCannotReconnectOrContinueBeforeRollback(): void
    {
        [$database, $pool] = $this->database();
        $pid = $this->pid($database);
        $connection = $pool->get()->getConnection();
        try {
            $connection->beginTransaction();
            $this->expectTimeout(fn () => $connection->query('SELECT pg_sleep(0.15)'));
            foreach ([fn () => $connection->getActiveConnection(), fn () => $connection->reconnect(), fn () => $connection->commit()] as $attempt) {
                try {
                    $attempt();
                    $this->fail('Invalid transaction must not reconnect or continue');
                } catch (QueryException) {
                    $this->assertSame(1, $connection->transactionLevel());
                }
            }
            $connection->rollBack();
            $this->assertSame(0, $connection->transactionLevel());
        } finally {
            $connection->release();
        }
        $this->assertNotSame($pid, $this->pid($database));
    }

    public function testRunCallbackFailureDoesNotRetireHealthySession(): void
    {
        [$database] = $this->database();
        $pid = $this->pid($database);
        foreach (['callback business failure', 'server closed the connection unexpectedly'] as $message) {
            $failure = new RuntimeException($message);
            $calls = 0;
            try {
                $database->run(function () use ($failure, &$calls): never {
                    ++$calls;
                    throw $failure;
                });
                $this->fail('Expected callback failure');
            } catch (RuntimeException $exception) {
                $this->assertSame($failure, $exception);
            }
            $this->assertSame(1, $calls);
            $this->assertSame($pid, $this->pid($database));
        }
        $this->assertFalse($database->run(fn () => false));
        $this->assertSame($pid, $this->pid($database));
        $this->expectTimeout(fn () => $database->run(function ($driver): void {
            if ($driver->query('SELECT pg_sleep(0.15)') === false) {
                throw new QueryException('native callback timed out');
            }
        }));
        $this->assertNotSame($pid, $this->pid($database));
    }

    public function testWaitingCoroutineAcquiresReplacementWithoutPoolLeak(): void
    {
        [$database, $pool] = $this->database();
        $oldPid = $this->pid($database);
        $connection = $pool->get()->getConnection();
        $native = $connection->run(fn ($driver) => WeakReference::create($driver));
        $result = new Channel(1);
        Coroutine::create(function () use ($pool, $result, $native): void {
            $borrowed = $pool->get();
            try {
                $closedBeforeAcquisition = $native->get() === null;
                $row = $borrowed->getConnection()->query('SELECT pg_backend_pid() AS pid')[0];
                $acquisition = ['closed' => $closedBeforeAcquisition, 'pid' => (int) $row['pid']];
            } catch (Throwable $exception) {
                $acquisition = $exception;
            } finally {
                $borrowed->release();
            }
            $result->push($acquisition);
        });
        $this->expectTimeout(fn () => $connection->query('SELECT pg_sleep(0.15)'));
        $connection->release();
        $acquisition = $result->pop(2);
        $this->assertIsArray($acquisition);
        $this->assertTrue($acquisition['closed']);
        $this->assertIsInt($acquisition['pid']);
        $this->assertNotSame($oldPid, $acquisition['pid']);
        $this->assertSame(1, $pool->getCurrentConnections());
        $this->assertSame(1, $pool->getConnectionsInChannel());
    }

    public function testTimeoutDoesNotReplayAnOperationWithDatabaseSideEffects(): void
    {
        [$database] = $this->database();
        $sequence = 'recovery_attempts_' . bin2hex(random_bytes(6));
        $database->execute('CREATE SEQUENCE ' . $sequence);
        try {
            $this->expectTimeout(fn () => $database->query("SELECT nextval('" . $sequence . "'), pg_sleep(0.15)"));
            $row = $database->query('SELECT CASE WHEN is_called THEN last_value ELSE 0 END AS attempts FROM ' . $sequence)[0];
            $this->assertSame(1, (int) $row['attempts']);
        } finally {
            $database->execute('DROP SEQUENCE ' . $sequence);
        }
    }

    public function testTerminatedSessionAbortsTransactionAndNextAcquisitionRecovers(): void
    {
        [$database, $pool] = $this->database();
        $pid = $this->pid($database);
        $connection = $pool->get()->getConnection();
        try {
            $connection->beginTransaction();
            $control = DB::connection('postgres');
            $control->query('SELECT pg_terminate_backend(?)', [$pid]);
            try {
                $connection->query('SELECT 1');
                $this->fail('Expected disconnected session');
            } catch (Exception) {
                $this->assertFalse($connection->check());
                $this->assertSame(1, $connection->transactionLevel());
            }
            $connection->rollBack();
        } finally {
            $connection->release();
        }
        $this->assertNotSame($pid, $this->pid($database));
    }

    public function testDatabaseFacadeCanLocallyAbortInvalidTransactionWithoutReconnectOrLeak(): void
    {
        [$database, $pool] = $this->database();
        $oldPid = $this->pid($database);
        $result = new Channel(1);
        $coroutine = Coroutine::create(function () use ($database, $result): void {
            try {
                $database->beginTransaction();
                $this->expectTimeout(fn () => $database->query('SELECT pg_sleep(0.15)'));
                $database->rollBack();
                $result->push($this->pid($database));
            } catch (Throwable $exception) {
                $result->push($exception);
            }
        });
        $this->assertTrue(Coroutine::join([$coroutine], 3));
        $newPid = $result->pop(0.1);
        $this->assertIsInt($newPid);
        $this->assertNotSame($oldPid, $newPid);
        $this->assertSame(1, $pool->getCurrentConnections());
        $this->assertSame(1, $pool->getConnectionsInChannel());
    }

    public function testConcurrentBorrowersHaveIndependentInvalidationState(): void
    {
        [$database, $pool] = $this->database(2);
        $damaged = $pool->get()->getConnection();
        $healthy = $pool->get()->getConnection();
        $healthyPid = (int) $healthy->query('SELECT pg_backend_pid() AS pid')[0]['pid'];
        $damagedPid = (int) $damaged->query('SELECT pg_backend_pid() AS pid')[0]['pid'];
        $result = new Channel(1);
        Coroutine::create(function () use ($damaged, $result): void {
            try {
                $this->expectTimeout(fn () => $damaged->query('SELECT pg_sleep(0.15)'));
                $result->push(true);
            } catch (Throwable $exception) {
                $result->push($exception);
            }
        });
        try {
            $this->assertSame($healthyPid, (int) $healthy->query('SELECT pg_backend_pid() AS pid')[0]['pid']);
            $this->assertTrue($result->pop(2));
            $this->assertFalse($damaged->check());
            $this->assertTrue($healthy->check());
            $this->assertSame($healthyPid, (int) $healthy->query('SELECT pg_backend_pid() AS pid')[0]['pid']);
        } finally {
            $damaged->release();
            $healthy->release();
        }
        $this->assertNotSame($damagedPid, $this->pid($database));
        $this->assertSame(2, $pool->getCurrentConnections());
        $this->assertSame(2, $pool->getConnectionsInChannel());
    }

    private function database(int $capacity = 1): array
    {
        $container = ApplicationContext::getContainer();
        $config = $container->get(ConfigInterface::class);
        $name = 'recovery_' . bin2hex(random_bytes(6));
        $settings = $config->get('db.postgres');
        $settings['pool']['min_connections'] = 1;
        $settings['pool']['max_connections'] = $capacity;
        $config->set('db.' . $name, $settings);
        $pool = $container->get(PoolFactory::class)->getPool($name);
        $this->pools[] = $pool;
        return [DB::connection($name), $pool];
    }

    private function pid(DB $database): int
    {
        return (int) $database->query('SELECT pg_backend_pid() AS pid')[0]['pid'];
    }

    private function expectTimeout(callable $operation): void
    {
        $timeout = Coroutine::getOptions()['socket_read_timeout'] ?? 60.0;
        Coroutine::set(['socket_read_timeout' => 0.02]);
        try {
            $operation();
            $this->fail('Expected an injected timeout');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        } finally {
            Coroutine::set(['socket_read_timeout' => $timeout]);
        }
    }
}
