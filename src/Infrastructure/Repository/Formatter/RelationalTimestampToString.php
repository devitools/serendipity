<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Formatter;

use Constructo\Contract\Formatter;
use Constructo\Type\Timestamp;

use function is_string;

class RelationalTimestampToString implements Formatter
{
    public function format(mixed $value, mixed $option = null): ?string
    {
        return match (true) {
            is_string($value) => $value,
            $value instanceof Timestamp => $value->toString(),
            default => null,
        };
    }
}
