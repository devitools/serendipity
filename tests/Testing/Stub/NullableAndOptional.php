<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

class NullableAndOptional
{
    public function __construct(
        public readonly ?string $nullable,
        public readonly int|string|null $union,
        public readonly int $optional = 10,
    ) {
    }
}
