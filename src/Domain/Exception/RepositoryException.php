<?php

declare(strict_types=1);

namespace Serendipity\Domain\Exception;

use Exception;
use Throwable;

use function is_int;

class RepositoryException extends Exception
{
    public readonly ThrowableType $type;

    public function __construct(
        public readonly string $context,
        ?Throwable $previous = null,
        ?ThrowableType $type = null,
        ?string $message = null,
        ?int $code = null,
    ) {
        $previousCode = $previous?->getCode();
        parent::__construct(
            $message ?? $previous?->getMessage() ?? '',
            $code ?? (is_int($previousCode) ? $previousCode : 0),
            $previous
        );

        $this->type = $type ?? ThrowableType::UNTREATED;
    }
}
