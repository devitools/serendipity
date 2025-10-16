<?php

declare(strict_types=1);

namespace Serendipity\Domain\Event;

use Constructo\Support\Set;

class ValidationFailedEvent
{
    public function __construct(
        public readonly string $resource,
        public readonly Set $values,
        public readonly string $message,
    ) {
    }
}
