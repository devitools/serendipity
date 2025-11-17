<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

class NullableAndOptional
{
    public function __construct(
        public readonly ?string $nullable,
        public readonly null|int|string $union,
        public readonly int $optional = 10,
    ) {
    }
}
