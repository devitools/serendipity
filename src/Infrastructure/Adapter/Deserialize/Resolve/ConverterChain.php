<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Adapter\Deserialize\Resolve;

use Serendipity\Domain\Support\Value;
use Serendipity\Infrastructure\Adapter\Deserialize\Chain;

use function is_object;
use function Serendipity\Type\Cast\toString;

class ConverterChain extends Chain
{
    public function resolve(mixed $value): Value
    {
        $type = $this->extractType($value);
        $conversor = $this->formatter($type);
        if ($conversor === null) {
            return parent::resolve($value);
        }
        return new Value($conversor->format($value));
    }

    private function extractType(mixed $value): string
    {
        $type = $this->type($value);
        if ($type === 'object' && is_object($value)) {
            $type = $value::class;
        }
        return toString($type);
    }
}
