<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Domain\Entity\Game;

use Constructo\Support\Entity;
use Serendipity\Domain\Support\Reflective\Attribute\Define;
use Serendipity\Domain\Support\Reflective\Definition\Type;

class Feature extends Entity
{
    public function __construct(
        #[Define(Type::JOB_TITLE)]
        public readonly string $name,
        #[Define(Type::SENTENCE)]
        public readonly string $description,
        public readonly bool $enabled,
    ) {
    }
}
