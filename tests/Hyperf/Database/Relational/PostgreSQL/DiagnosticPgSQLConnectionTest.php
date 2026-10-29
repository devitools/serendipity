<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use Closure;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\PoolOptionInterface;
use Hyperf\DB\DB;
use Hyperf\DB\Exception\QueryException;
use Hyperf\Pool\Pool;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\DiagnosticPgSQLConnection;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLCommands;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLConnector;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLDiagnostics;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailureClassifier;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLSession;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLStatements;
use Swoole\Coroutine\PostgreSQL;
use Swoole\Coroutine\PostgreSQLStatement;

use function array_shift;
use function sprintf;

class DiagnosticPgSQLConnectionTest extends TestCase
{
    public function testInvalidQueryPreservesOriginalPostgresError(): void
    {
        $database = DB::connection('postgres');

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('syntax error');

        $database->query('SELECT FROM');
    }

    public function testInheritedRetryNeverReconnectsOrRepeatsLostConnectionOperations(): void
    {
        foreach (['server closed the connection unexpectedly', 'no connection to the server',
            'Resource deadlock avoided', 'Name or service not known'] as $message) {
            $reconnections = 0;
            $driver = $this->driver();
            $driver->expects($this->never())->method('prepare');
            $connection = $this->connection($driver, static function () use (&$reconnections, $driver): PostgreSQL {
                ++$reconnections;
                return $driver;
            });
            $failure = new QueryException($message);
            try {
                $connection->retry($failure, 'execute', ['INSERT INTO example VALUES (1)']);
                $this->fail('Expected original failure without replay');
            } catch (QueryException $exception) {
                $this->assertSame($failure, $exception);
            }
            $this->assertSame(0, $reconnections);
        }
    }

    public function testNativeCloseIsIdempotentAndClosedTransactionOnlyRollsBackLocally(): void
    {
        $driver = $this->driver();
        $driver->expects($this->once())->method('query')->willReturn($this->statement());
        $connection = $this->connection($driver);
        $connection->beginTransaction();
        $connection->close();
        $connection->close();
        $this->assertFalse($connection->check());
        $this->assertSame($connection, $connection->getConnection());
        try {
            $connection->query('SELECT 1');
            $this->fail('Expected closed session guard');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('not executed', $exception->getMessage());
        }
        $connection->rollBack();
        $this->assertSame(0, $connection->transactionLevel());
    }

    public function testBeginAndCommitAreNotRetriedAndInvalidRollbackIsLocal(): void
    {
        foreach (['BEGIN', 'COMMIT'] as $failedCommand) {
            $driver = $this->driver();
            $success = $this->statement();
            $commands = [];
            $driver->method('query')->willReturnCallback(function (string $query) use (&$commands, $driver, $success, $failedCommand) {
                $commands[] = $query;
                if ($query === $failedCommand) {
                    $driver->error = 'server closed the connection unexpectedly';
                    return false;
                }
                return $success;
            });
            $connection = $this->connection($driver);
            try {
                $connection->beginTransaction();
                $connection->commit();
                $this->fail('Expected transaction failure');
            } catch (QueryException) {
                $connection->rollBack();
                $this->assertSame(0, $connection->transactionLevel());
            }
            $this->assertSame($failedCommand === 'BEGIN' ? ['BEGIN'] : ['BEGIN', 'COMMIT'], $commands);
        }
    }

    public function testReconnectFailureAndSchemaFailureRemainUnusableUntilSuccessfulAcquisition(): void
    {
        foreach (['connect', 'schema'] as $failedStage) {
            $old = $this->driver();
            $old->method('query')->willReturn($this->statement());
            $bad = $this->driver();
            $bad->expects($this->once())->method('connect')->willReturn($failedStage !== 'connect');
            $bad->method('query')->willReturn(false);
            $good = $this->driver();
            $good->expects($this->once())->method('connect')->willReturn(true);
            $good->method('query')->willReturn($this->statement());
            $drivers = [$bad, $good];
            $connection = $this->connection($old, static function () use (&$drivers): PostgreSQL {
                return array_shift($drivers);
            }, ['schema' => 'public']);
            $connection->close();
            $connection->close();
            try {
                $connection->getConnection();
                $this->fail('Expected failed reconnection');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('PostgreSQL ' . $failedStage . ' failed', $exception->getMessage());
            }
            $this->assertFalse($connection->check());
            $this->assertSame($connection, $connection->getConnection());
            $this->assertTrue($connection->check());
        }
    }

    public function testHealthyActiveTransactionKeepsItsSessionDespiteIdleExpiration(): void
    {
        $driver = $this->driver();
        $driver->method('query')->willReturn($this->statement());
        $connection = $this->connection($driver, fn () => throw new RuntimeException('Unexpected reconnection'), ['pool' => ['max_idle_time' => 0.0]]);
        $connection->beginTransaction();
        $this->assertSame($connection, $connection->getConnection());
        $connection->rollBack();
    }

    public function testReleaseRollsBackHealthyTransactionAndRetiresFailedRollback(): void
    {
        foreach ([true, false] as $rollbackSucceeds) {
            $driver = $this->driver();
            $success = $this->statement();
            $queries = [];
            $driver->method('query')->willReturnCallback(static function (string $query) use (&$queries, $rollbackSucceeds, $success) {
                $queries[] = $query;
                return $query === 'ROLLBACK' && ! $rollbackSucceeds ? false : $success;
            });
            $connection = $this->connection($driver);
            $connection->beginTransaction();
            $connection->release();
            $this->assertSame(['BEGIN', 'ROLLBACK'], $queries);
            $this->assertSame(0, $connection->transactionLevel());
            $this->assertSame($rollbackSucceeds, $connection->check());
        }
    }

    public function testNativeCallbackIsBoundToAdapterAndReceivesItsClient(): void
    {
        $driver = $this->driver();
        $connection = $this->connection($driver);

        $this->assertSame($driver, $connection->run(fn ($client) => $client));
        $this->assertTrue($connection->run(fn () => $this instanceof DiagnosticPgSQLConnection));
    }

    private function driver(?string $error = '', string $sqlstate = ''): PostgreSQL
    {
        $driver = $this->createMock(PostgreSQL::class);
        $driver->error = $error;
        $driver->errCode = 0;
        $driver->resultStatus = 0;
        $driver->resultDiag = $sqlstate !== '' ? ['sqlstate' => $sqlstate] : [];
        return $driver;
    }

    private function statement(string $error = '', string $sqlstate = ''): PostgreSQLStatement
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $settings = $config->get('db.postgres');
        $native = new PostgreSQL();
        $native->connect(sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s',
            $settings['host'],
            $settings['port'],
            $settings['database'],
            $settings['username'],
            $settings['password']
        ));
        $statement = $native->prepare('SELECT 1');
        $statement->error = $error;
        $statement->errCode = 0;
        $statement->resultStatus = 0;
        $statement->resultDiag = $sqlstate !== '' ? ['sqlstate' => $sqlstate] : [];
        return $statement;
    }

    private function connection(PostgreSQL $driver, ?Closure $factory = null, array $config = []): DiagnosticPgSQLConnection
    {
        $driver->method('connect')->willReturn(true);
        $connector = $this->getMockBuilder(PgSQLConnector::class)->onlyMethods(['create'])->getMock();
        $initial = true;
        $connector->method('create')->willReturnCallback(static function () use ($driver, $factory, &$initial): PostgreSQL {
            if ($initial) {
                $initial = false;
                return $driver;
            }
            return $factory !== null ? $factory() : $driver;
        });
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $option = $this->createMock(PoolOptionInterface::class);
        $option->method('getMaxIdleTime')->willReturn($config['pool']['max_idle_time'] ?? 60.0);
        $pool = $this->createMock(Pool::class);
        $pool->method('getOption')->willReturn($option);
        $session = new PgSQLSession(new PgSQLFailureClassifier(), new PgSQLDiagnostics(new NullLogger()), 'test');
        return new DiagnosticPgSQLConnection($container, $pool, $config, $session, $connector, new PgSQLStatements(), new PgSQLCommands());
    }
}
