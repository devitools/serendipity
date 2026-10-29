<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL;

use Swoole\Coroutine\PostgreSQL;
use Swoole\Coroutine\PostgreSQLStatement;

use function array_filter;
use function Constructo\Cast\arrayify;
use function Constructo\Cast\integerify;
use function Constructo\Cast\stringify;
use function sprintf;

final readonly class PgSQLFailure
{
    public function __construct(
        public string $error,
        public string $sqlstate,
        public string $detail,
        public int $code,
        public int $status,
    ) {
    }

    public static function from(null|PostgreSQL|PostgreSQLStatement $driver, ?PostgreSQL $fallback = null): self
    {
        /** @var array<string, mixed> $diagnostics */
        $diagnostics = arrayify($driver->resultDiag ?? $fallback->resultDiag ?? []);
        $failure = new self(
            stringify($driver->error ?? ''),
            stringify($diagnostics['sqlstate'] ?? ''),
            stringify($diagnostics['message_primary'] ?? ''),
            integerify($driver->errCode ?? 0),
            integerify($driver->resultStatus ?? 0),
        );
        return $failure->isEmpty() && $fallback !== null ? self::from($fallback) : $failure;
    }

    public function isEmpty(): bool
    {
        return array_filter([$this->error, $this->sqlstate, $this->detail, $this->code, $this->status]) === [];
    }

    public function message(string $stage): string
    {
        if ($this->error !== '') {
            return $this->error;
        }
        return sprintf(
            'PostgreSQL %s failed (SQLSTATE %s, error code %d, result status %d): %s',
            $stage,
            $this->sqlstate !== '' ? $this->sqlstate : 'unknown',
            $this->code,
            $this->status,
            $this->detail !== '' ? $this->detail : 'The driver returned no error message',
        );
    }
}
