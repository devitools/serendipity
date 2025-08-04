<?php

declare(strict_types=1);

namespace Serendipity\Presentation;

use Constructo\Contract\Message;
use Constructo\Support\Set;

class Output implements Message
{
    private readonly Set $properties;

    public function __construct(
        private readonly mixed $content = null,
        array $properties = []
    ) {
        $this->properties = Set::createFrom($properties);
    }

    public function properties(): Set
    {
        return $this->properties;
    }

    public function content(): mixed
    {
        return $this->content;
    }
}
