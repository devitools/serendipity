<?php

declare(strict_types=1);

namespace Serendipity\Domain\Contract\Support;

use DateTimeImmutable;
use Serendipity\Domain\Exception\Parser\Thrown;
use Throwable;

interface ThrownFactory
{
    public function make(Throwable $throwable, DateTimeImmutable $at = new DateTimeImmutable()): Thrown;
}
