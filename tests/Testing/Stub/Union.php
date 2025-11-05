<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use DateTimeImmutable;
use DateTimeInterface;
use PDO;
use stdClass;

class Union
{
    public function __construct(
        public readonly int|string $builtin,
        public readonly int|string|null $nullable,
        public readonly DateTimeInterface|stdClass $native,
        public readonly DateTimeImmutable|PDO $more,
    ) {
    }
}
