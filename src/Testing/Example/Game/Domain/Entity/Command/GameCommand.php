<?php

declare(strict_types=1);

namespace Serendipity\Testing\Example\Game\Domain\Entity\Command;

use Serendipity\Domain\Entity\Entity;

class GameCommand extends Entity
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly array $data = [],
    ) {
    }
}
