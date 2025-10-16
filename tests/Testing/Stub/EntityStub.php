<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use Constructo\Support\Entity;
use DateTime;
use Serendipity\Test\Testing\Stub\Type\SingleBacked;

class EntityStub extends Entity
{
    public function __construct(
        public readonly int $id,
        public readonly float $price,
        public readonly string $name,
        public readonly bool $isActive,
        public readonly NoConstructor $more,
        public readonly ?DateTime $createdAt,
        public readonly ?NoParameters $no,
        public readonly array $tags = [],
        public readonly SingleBacked $enum = SingleBacked::ONE,
        ?string $foo = null,
    ) {
    }
}
