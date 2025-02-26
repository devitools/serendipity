<?php

declare(strict_types=1);

namespace Serendipity\Presentation\Output;

use Serendipity\Domain\Contract\Result;
use Serendipity\Domain\Support\Values;

class Output implements Result
{
    private readonly Values $properties;

    private readonly ?Values $content;

    public function __construct(
        array $properties = [],
        ?array $content = null
    ) {
        $this->properties = Values::createFrom($properties);
        $this->content = $content === null ? null : Values::createFrom($content);
    }

    public function properties(): Values
    {
        return $this->properties;
    }

    public function content(): ?Values
    {
        return $this->content;
    }
}
