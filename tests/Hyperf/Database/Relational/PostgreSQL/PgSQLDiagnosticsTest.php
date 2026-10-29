<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLDiagnostics;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailure;

class PgSQLDiagnosticsTest extends TestCase
{
    public function testInvalidationLogsOnlySanitizedDiagnosticFields(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with(
            '[postgres] session invalidated',
            ['stage' => 'execute', 'reason' => 'connection_lost', 'sqlstate' => '08006',
                'driver_code' => 104, 'result_status' => 7, 'pool' => 'manager'],
        );
        $diagnostics = new PgSQLDiagnostics($logger);
        $failure = new PgSQLFailure('secret SQL and bindings', '08006', 'private credentials', 104, 7);

        $diagnostics->invalidated('execute', $failure, 'connection_lost', 'manager');
    }

    public function testSuccessfulCleanupRunsOnceWithoutLogging(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $diagnostics = new PgSQLDiagnostics($logger);
        $calls = 0;

        $diagnostics->clean('close', static function () use (&$calls): void {
            ++$calls;
        }, 'manager');

        $this->assertSame(1, $calls);
    }

    public function testFailedCleanupIsLoggedWithoutRetryingOrEscaping(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with(
            '[postgres] session cleanup failed',
            ['stage' => 'close', 'exception' => RuntimeException::class, 'pool' => 'manager'],
        );
        $diagnostics = new PgSQLDiagnostics($logger);
        $calls = 0;

        $diagnostics->clean('close', static function () use (&$calls): void {
            ++$calls;
            throw new RuntimeException('private native error');
        }, 'manager');

        $this->assertSame(1, $calls);
    }

    public function testLoggerFailureCannotEscapeInvalidationOrCleanup(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('warning')->willThrowException(new RuntimeException('logger failed'));
        $diagnostics = new PgSQLDiagnostics($logger);

        $diagnostics->invalidated('prepare', new PgSQLFailure('', '', '', 0, 0), 'unknown_native_failure', 'manager');
        $diagnostics->clean('close', static fn () => throw new RuntimeException('close failed'), 'manager');
    }
}
