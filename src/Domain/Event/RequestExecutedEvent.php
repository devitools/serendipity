<?php

declare(strict_types=1);

namespace Serendipity\Domain\Event;

use Constructo\Contract\Message;

class RequestExecutedEvent
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $options,
        public readonly ?Message $message = null,
    ) {
    }
}
