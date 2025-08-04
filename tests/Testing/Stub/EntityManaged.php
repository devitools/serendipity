<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use Constructo\Support\Reflective\Attribute\Managed;
use DateTimeImmutable;

class EntityManaged
{
    public function __construct(
        #[Managed(management: 'id')]
        public readonly string $id,
        #[Managed(management: 'timestamp')]
        public readonly DateTimeImmutable $createdAt,
        #[Managed(management: 'timestamp')]
        public readonly DateTimeImmutable $updatedAt,
        public readonly string $name,
    ) {
    }
}
