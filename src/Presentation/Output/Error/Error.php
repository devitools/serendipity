<?php

declare(strict_types=1);

namespace Serendipity\Presentation\Output\Error;

use Serendipity\Presentation\Output;

abstract class Error extends Output
{
    final public function __construct(int|string|null $content, array $properties = [])
    {
        parent::__construct($content, $properties);
    }

    final public static function createFrom(int|string|null $content = null, array $properties = []): static
    {
        return new static($content, $properties);
    }
}
