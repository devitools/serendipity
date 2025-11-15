<?php

declare(strict_types=1);

namespace Serendipity\Domain\Exception\Parser;

use DateTimeImmutable;
use Serendipity\Domain\Contract\Support\ThrownFactory;
use Serendipity\Domain\Exception\ThrowableType;
use Throwable;

class DefaultThrownFactory implements ThrownFactory
{
    public function __construct(private readonly array $classification = [])
    {
    }

    public function make(Throwable $throwable, DateTimeImmutable $at = new DateTimeImmutable()): Thrown
    {
        return new Thrown(
            $this->type($throwable::class),
            $at,
            $throwable::class,
            $throwable->getMessage(),
            $throwable->getCode(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTrace(),
            $this->previous($throwable->getPrevious()),
        );
    }

    private function previous(?Throwable $getPrevious): ?Thrown
    {
        if ($getPrevious === null) {
            return null;
        }
        return $this->make($getPrevious);
    }

    private function type(string $throwable): ThrowableType
    {
        $type = $this->classification[$throwable] ?? null;
        if ($type instanceof ThrowableType) {
            return $type;
        }
        return match ($type) {
            ThrowableType::INVALID_INPUT->value => ThrowableType::INVALID_INPUT,
            default => ThrowableType::UNTREATED,
        };
    }
}
