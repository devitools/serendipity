<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Closure;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class PgSQLDiagnostics
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function invalidated(string $stage, PgSQLFailure $failure, string $reason, string $pool): void
    {
        $this->log('[postgres] session invalidated', [
            'stage' => $stage,
            'reason' => $reason,
            'sqlstate' => $failure->sqlstate,
            'driver_code' => $failure->code,
            'result_status' => $failure->status,
            'pool' => $pool,
        ]);
    }

    public function clean(string $stage, Closure $callback, string $pool): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            $this->log('[postgres] session cleanup failed', [
                'stage' => $stage,
                'exception' => $exception::class,
                'pool' => $pool,
            ]);
        }
    }

    private function log(string $message, array $context): void
    {
        try {
            $this->logger->warning($message, $context);
        } catch (Throwable) {
        }
    }
}
