<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use stdClass;

class Native
{
    public function __construct(
        public readonly Closure $callable,
        public readonly stdClass $stdClass,
        public readonly DateTimeImmutable $dateTimeImmutable,
        public readonly DateTime $dateTime,
        public readonly DateTimeInterface $dateTimeInterface,
    ) {
    }
}
