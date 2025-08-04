<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Formatter;

use ArrayObject;
use Constructo\Contract\Formatter;

class MongoArrayToEntity implements Formatter
{
    public function format(mixed $value, mixed $option = null): ?array
    {
        return match (true) {
            $value instanceof ArrayObject => $this->map($value->getArrayCopy()),
            is_array($value) => $value,
            default => null
        };
    }

    private function map(array $value): array
    {
        $callback = fn (mixed $element): mixed => match (true) {
            is_scalar($element) => $element,
            default => $this->format($element),
        };
        return array_map($callback, $value);
    }
}
