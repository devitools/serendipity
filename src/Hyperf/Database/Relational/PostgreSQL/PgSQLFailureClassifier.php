<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use ErrorException;
use Swoole\Coroutine\PostgreSQL;
use Throwable;

use function in_array;
use function preg_match;
use function preg_quote;
use function str_contains;
use function str_starts_with;
use function strtolower;

final class PgSQLFailureClassifier
{
    public function reason(PgSQLFailure $failure, ?Throwable $exception = null): ?string
    {
        if ($failure->sqlstate !== '') {
            return $this->sqlstateReason($failure->sqlstate) ?? $this->nativeReason($failure, $exception);
        }
        $message = $failure->error . ' ' . $failure->detail . ' ' . ($exception?->getMessage() ?? '');
        $reason = $this->messageReason(strtolower($message));
        if ($reason !== null) {
            return $reason;
        }
        return $failure->code !== 0 ? 'driver_error' : null;
    }

    public function failureReason(PgSQLFailure $failure): ?string
    {
        return $this->reason($failure) ?? ($failure->sqlstate === '' ? 'unknown_native_failure' : null);
    }

    private function sqlstateReason(string $sqlstate): ?string
    {
        if (str_starts_with($sqlstate, '08')) {
            return 'connection_lost';
        }
        return in_array($sqlstate, ['57P01', '57P02', '57P03'], true) ? 'server_shutdown' : null;
    }

    private function nativeReason(PgSQLFailure $failure, ?Throwable $exception): ?string
    {
        $reason = $this->nativeMessageReason(strtolower($failure->error));
        if ($reason !== null) {
            return $reason;
        }
        if ($failure->code !== 0) {
            return 'driver_error';
        }
        return $this->nativeWarningReason($exception);
    }

    private function nativeWarningReason(?Throwable $exception): ?string
    {
        if (! $exception instanceof ErrorException) {
            return null;
        }
        $pattern = '/^' . preg_quote(PostgreSQL::class, '/') . '(?:Statement)?::\w+\(\): (.*)$/s';
        if (preg_match($pattern, $exception->getMessage(), $matches) !== 1) {
            return null;
        }
        return $this->nativeMessageReason(strtolower($matches[1]));
    }

    private function nativeMessageReason(string $message): ?string
    {
        foreach ($this->patterns() as $pattern => $reason) {
            if (str_starts_with($message, $pattern)) {
                return $reason;
            }
        }
        return null;
    }

    private function messageReason(string $message): ?string
    {
        foreach ($this->patterns() as $pattern => $reason) {
            if (str_contains($message, $pattern)) {
                return $reason;
            }
        }
        return null;
    }

    /** @return array<string, string> */
    private function patterns(): array
    {
        return [
            'coroutine timedout' => 'client_timeout',
            'coroutine canceled' => 'client_cancelled',
            'coroutine cancelled' => 'client_cancelled',
            'swoole_event_add failed' => 'reactor_failure',
            'swoole_event_del failed' => 'reactor_failure',
            'bad file descriptor' => 'reactor_failure',
            'another command is already in progress' => 'protocol_busy',
            'server closed the connection unexpectedly' => 'connection_lost',
            'no connection to the server' => 'connection_lost',
            'ssl connection has been closed unexpectedly' => 'connection_lost',
            'could not receive data from server' => 'connection_lost',
            'could not send data to server' => 'connection_lost',
            'reset by peer' => 'connection_lost',
            'broken pipe' => 'connection_lost',
            'onerror' => 'connection_lost',
        ];
    }
}
