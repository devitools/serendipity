<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use ErrorException;
use Hyperf\DB\Exception\QueryException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLDiagnostics;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailure;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailureClassifier;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLSession;
use Swoole\Coroutine\PostgreSQL;

class PgSQLSessionTest extends TestCase
{
    public function testNewSessionRejectsUseUntilActivated(): void
    {
        $session = $this->session();
        $this->assertFalse($session->isValid());
        $this->expectException(QueryException::class);

        $session->usable($this->driver());
    }

    public function testActiveSessionReturnsItsClientAndRejectsMissingClient(): void
    {
        $session = $this->session();
        $driver = $this->driver();
        $session->activate();
        $this->assertSame($driver, $session->usable($driver));
        $this->expectException(QueryException::class);

        $session->usable(null);
    }

    public function testNativeCallRunsOnceAndPreservesTheOriginalFailure(): void
    {
        $session = $this->session();
        $session->activate();
        $driver = $this->driver();
        $failure = new ErrorException('swoole_event_add failed');
        $driver->expects($this->once())->method('prepare')->willThrowException($failure);
        try {
            $session->call('prepare', $driver, $driver, fn () => $driver->prepare('SELECT 1'));
            $this->fail('Expected native failure');
        } catch (ErrorException $actual) {
            $this->assertSame($failure, $actual);
        }
        $this->assertFalse($session->isValid());
    }

    public function testNativeCallPreservesSuccessfulResult(): void
    {
        $session = $this->session();
        $session->activate();
        $driver = $this->driver();
        $calls = 0;

        $result = $session->call('execute', $driver, $driver, static function () use (&$calls): int {
            ++$calls;
            return 42;
        });

        $this->assertSame(42, $result);
        $this->assertSame(1, $calls);
        $this->assertTrue($session->isValid());
    }

    public function testStructuredSqlFailureDoesNotInvalidateSession(): void
    {
        $session = $this->session();
        $session->activate();
        $driver = $this->driver('syntax error', '42601');
        try {
            $session->failure('execute', $driver, $driver);
            $this->fail('Expected SQL failure');
        } catch (QueryException $actual) {
            $this->assertSame('syntax error', $actual->getMessage());
        }
        $this->assertTrue($session->isValid());
    }

    public function testUnknownNativeFailureInvalidatesAndProvidesFallbackMessage(): void
    {
        $session = $this->session();
        $session->activate();
        $driver = $this->driver(null);
        try {
            $session->failure('prepare', $driver, $driver);
            $this->fail('Expected unknown native failure');
        } catch (QueryException $actual) {
            $this->assertStringContainsString('The driver returned no error message', $actual->getMessage());
        }
        $this->assertFalse($session->isValid());
    }

    public function testInvalidationLogsOnceAndCanBeResetForANewClient(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('warning');
        $session = $this->session($logger);
        $failure = new PgSQLFailure('', '08006', '', 0, 7);
        $session->activate();

        $session->invalidate('execute', $failure, null);
        $this->assertTrue($session->isValid());
        $session->invalidate('execute', $failure, 'connection_lost');
        $session->invalidate('fetch', $failure, 'connection_lost');
        $this->assertFalse($session->isValid());
        $session->activate();
        $session->invalidate('execute', $failure, 'connection_lost');
        $this->assertFalse($session->isValid());
    }

    public function testDiscardInvalidatesBeforeCleanupAndRemainsInvalidIfCleanupFails(): void
    {
        $session = $this->session();
        $session->activate();
        $calls = 0;

        $session->discard(function () use ($session, &$calls): void {
            $this->assertFalse($session->isValid());
            ++$calls;
            throw new RuntimeException('close failed');
        });

        $this->assertSame(1, $calls);
        $this->assertFalse($session->isValid());
    }

    private function session(?LoggerInterface $logger = null): PgSQLSession
    {
        return new PgSQLSession(new PgSQLFailureClassifier(), new PgSQLDiagnostics($logger ?? new NullLogger()), 'test');
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
