<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Adapter\Deserialize\Resolve;

use Serendipity\Domain\Support\Value;
use Serendipity\Infrastructure\Adapter\Deserialize\Chain;

class DoNothingChain extends Chain
{
    public function resolve(mixed $value): Value
    {
        return new Value($value);
    }
}
