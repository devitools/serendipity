<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub\Input;

use Serendipity\Presentation\ReflectorInput;

class StubReflectorInput extends ReflectorInput
{
    public function testFallback(array $data, string $field): ?string
    {
        return $this->fallback($data, $field);
    }
}
