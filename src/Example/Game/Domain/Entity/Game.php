<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Domain\Entity;

use Constructo\Support\Reflective\Attribute\Managed;
use Constructo\Support\Reflective\Attribute\Pattern;
use Constructo\Type\Timestamp;
use Serendipity\Example\Game\Domain\Collection\Game\FeatureCollection;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;

class Game extends GameCommand
{
    public function __construct(
        #[Managed('id')]
        public readonly string $id,
        #[Managed('timestamp')]
        public readonly Timestamp $createdAt,
        #[Managed('timestamp')]
        public readonly Timestamp $updatedAt,
        #[Pattern('/^[a-zA-Z]{1,255}$/')]
        string $name,
        #[Pattern]
        string $slug,
        Timestamp $publishedAt,
        array $data,
        FeatureCollection $features,
    ) {
        parent::__construct(
            name: $name,
            slug: $slug,
            publishedAt: $publishedAt,
            data: $data,
            features: $features,
        );
    }
}
