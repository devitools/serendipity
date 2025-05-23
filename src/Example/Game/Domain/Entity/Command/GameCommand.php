<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Domain\Entity\Command;

use Serendipity\Domain\Entity\Entity;
use Serendipity\Domain\Type\Timestamp;
use Serendipity\Example\Game\Domain\Collection\Game\FeatureCollection;

class GameCommand extends Entity
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly Timestamp $publishedAt,
        public readonly array $data,
        public readonly FeatureCollection $features,
    ) {
    }
}
