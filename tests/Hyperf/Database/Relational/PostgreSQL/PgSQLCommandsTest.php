<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use ErrorException;
use Hyperf\DB\AbstractConnection;
use Hyperf\DB\Exception\QueryException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLCommands;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLDiagnostics;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailure;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailureClassifier;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLSession;
use Swoole\Coroutine\PostgreSQL;

class PgSQLCommandsTest extends TestCase
{
    public function testFailedNativeCommandExecutesOnceAndInvalidatesSession(): void
    {
        $commands = new PgSQLCommands();
        $session = $this->session();
        $driver = $this->driver('server closed the connection unexpectedly');
        $driver->expects($this->once())->method('query')->with('COMMIT')->willReturn(false);
        try {
            $commands->call($session, $driver, 'commit', fn () => $driver->query('COMMIT'));
            $this->fail('Expected command failure');
        } catch (QueryException $exception) {
            $this->assertSame('server closed the connection unexpectedly', $exception->getMessage());
        }
        $this->assertFalse($session->isValid());
    }

    public function testSuccessfulCommandPreservesResult(): void
    {
        $commands = new PgSQLCommands();
        $session = $this->session();
        $driver = $this->driver();
        $calls = 0;

        $result = $commands->call($session, $driver, 'custom', static function () use (&$calls): int {
            ++$calls;
            return 42;
        });

        $this->assertSame(42, $result);
        $this->assertSame(1, $calls);
        $this->assertTrue($session->isValid());
    }

    public function testApplicationCallbackFailureIsPreservedWithoutInvalidatingOrReplaying(): void
    {
        $commands = new PgSQLCommands();
        foreach ([new RuntimeException('server closed the connection unexpectedly'), new ErrorException('application error')] as $failure) {
            $session = $this->session();
            $calls = 0;
            try {
                $commands->run($session, $this->driver(), static function () use ($failure, &$calls): never {
                    ++$calls;
                    throw $failure;
                });
                $this->fail('Expected application failure');
            } catch (ErrorException|RuntimeException $exception) {
                $this->assertSame($failure, $exception);
            }
            $this->assertSame(1, $calls);
            $this->assertTrue($session->isValid());
        }
    }

    public function testNativeCallbackWarningPreservesExceptionAndRetiresSession(): void
    {
        $commands = new PgSQLCommands();
        $session = $this->session();
        $failure = new ErrorException('Swoole\Coroutine\PostgreSQL::query(): swoole_event_add failed');
        try {
            $commands->run($session, $this->driver(), static fn () => throw $failure);
            $this->fail('Expected native warning');
        } catch (ErrorException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertFalse($session->isValid());
    }

    public function testFalseCallbackResultIsPreservedAndNativeDiagnosticsDetermineValidity(): void
    {
        $commands = new PgSQLCommands();
        foreach (['' => true, 'Coroutine canceled' => false] as $error => $valid) {
            $session = $this->session();

            $this->assertFalse($commands->run($session, $this->driver($error), static fn () => false));
            $this->assertSame($valid, $session->isValid());
        }
    }

    public function testHealthyReleaseRollsBackBeforeReturningWrapperWithoutClosingClient(): void
    {
        $commands = new PgSQLCommands();
        $session = $this->session();
        $order = [];
        $connection = $this->createMock(AbstractConnection::class);
        $connection->method('transactionLevel')->willReturn(1);
        $connection->expects($this->once())->method('rollBack')->with(0)->willReturnCallback(static function () use (&$order): void {
            $order[] = 'rollback';
        });
        $connection->expects($this->never())->method('close');

        $commands->release($session, $connection, static fn () => throw new RuntimeException('Unexpected snapshot'), static function () use (&$order): void {
            $order[] = 'release';
        });

        $this->assertSame(['rollback', 'release'], $order);
        $this->assertTrue($session->isValid());
    }

    public function testRollbackFailureInvalidatesBeforeLocalCleanupAndRelease(): void
    {
        $commands = new PgSQLCommands();
        $session = $this->session();
        $connection = $this->createMock(AbstractConnection::class);
        $connection->method('transactionLevel')->willReturn(1);
        $order = [];
        $rollbacks = 0;
        $connection->expects($this->exactly(2))->method('rollBack')->with(0)->willReturnCallback(function () use ($session, &$rollbacks, &$order): void {
            if (++$rollbacks === 1) {
                $order[] = 'failed rollback';
                throw new RuntimeException('SQL rollback failed');
            }
            $this->assertFalse($session->isValid());
            $order[] = 'local cleanup';
        });
        $connection->expects($this->once())->method('close')->willReturnCallback(static function () use (&$order): bool {
            $order[] = 'close';
            return true;
        });

        $commands->release($session, $connection, static fn () => new PgSQLFailure('SQL rollback failed', 'XX000', '', 0, 7), static function () use (&$order): void {
            $order[] = 'release';
        });

        $this->assertSame(['failed rollback', 'local cleanup', 'close', 'release'], $order);
        $this->assertFalse($session->isValid());
    }

    public function testInvalidSessionClosesBeforeReturningWrapper(): void
    {
        $commands = new PgSQLCommands();
        $session = $this->session();
        $session->invalidate('execute', new PgSQLFailure('', '', '', 0, 0), 'unknown_native_failure');
        $connection = $this->createMock(AbstractConnection::class);
        $connection->method('transactionLevel')->willReturn(0);
        $closed = false;
        $connection->expects($this->once())->method('close')->willReturnCallback(static function () use (&$closed): bool {
            $closed = true;
            return true;
        });
        $released = 0;

        $commands->release($session, $connection, static fn () => throw new RuntimeException('Unexpected snapshot'), function () use (&$closed, &$released): void {
            $this->assertTrue($closed);
            ++$released;
        });

        $this->assertSame(1, $released);
    }

    public function testReleaseAndLoggerFailuresCannotEscapeOrCauseDoubleRelease(): void
    {
        $commands = new PgSQLCommands();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->willThrowException(new RuntimeException('logger failed'));
        $session = $this->session($logger);
        $connection = $this->createMock(AbstractConnection::class);
        $connection->method('transactionLevel')->willReturn(0);
        $connection->expects($this->never())->method('close');
        $releases = 0;

        $commands->release($session, $connection, static fn () => throw new RuntimeException('Unexpected snapshot'), static function () use (&$releases): void {
            ++$releases;
            throw new RuntimeException('pool release failed');
        });

        $this->assertSame(1, $releases);
        $this->assertTrue($session->isValid());
    }

    private function session(?LoggerInterface $logger = null): PgSQLSession
    {
        $session = new PgSQLSession(new PgSQLFailureClassifier(), new PgSQLDiagnostics($logger ?? new NullLogger()), 'test');
        $session->activate();
        return $session;
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
}
