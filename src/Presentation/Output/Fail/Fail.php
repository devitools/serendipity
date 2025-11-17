<?php

declare(strict_types=1);

namespace Serendipity\Presentation\Output\Fail;

use Serendipity\Presentation\Output;

abstract class Fail extends Output
{
    final public function __construct(null|array|int|string $content, array $properties = [])
    {
        parent::__construct($content, $properties);
    }

    final public static function createFrom(null|array|int|string $content = null, array $properties = []): static
    {
        return new static($content, $properties);
    }
}
