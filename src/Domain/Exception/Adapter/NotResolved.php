<?php

declare(strict_types=1);

namespace Serendipity\Domain\Exception\Adapter;

use function sprintf;

final readonly class NotResolved
{
    public function __construct(
        public Type $type,
        public string $field = '',
        public mixed $value = null,
    ) {
    }

    public function message(): string
    {
        return match ($this->type) {
            Type::REQUIRED => sprintf(
                "The value for '%s' is required and was not provided.",
                $this->field
            ),
            Type::INVALID => sprintf(
                "The value for '%s' is not of the expected type.",
                $this->field
            ),
        };
    }
}
