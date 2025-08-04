<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Formatter;

use Constructo\Contract\Formatter;
use DateTimeInterface;

use function is_string;

class RelationalDatetimeToString implements Formatter
{
    public function format(mixed $value, mixed $option = null): ?string
    {
        return match (true) {
            is_string($value) => $value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            default => null,
        };
    }
}
