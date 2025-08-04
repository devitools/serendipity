<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\Support;

use Serendipity\Domain\Exception\UniqueKeyViolationException;
use Throwable;

use function Constructo\Cast\stringify;

trait HasPostgresUniqueConstraint
{
    protected function detectUniqueKeyViolation(Throwable $exception): ?UniqueKeyViolationException
    {
        $message = $exception->getMessage();
        $pattern = '/duplicate key value violates unique constraint\s+?"([^"]+)"\s+.*\(([^)]+)\)=\(([^)]+)\).*/m';
        if (! preg_match($pattern, $message, $matches)) {
            return null;
        }
        $resource = stringify($matches[1]);
        $key = stringify($matches[2]);
        $value = stringify($matches[3]);
        return new UniqueKeyViolationException($key, $value, $resource, $exception);
    }
}
