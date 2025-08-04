<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\ReflectorInput;

// Minimal testable implementation that doesn't require dependencies
class TestableReflectorInput
{
    public function testFallback(array $data, string $field): ?string
    {
        return isset($data[$field])
            ? $field
            : null;
    }
}
