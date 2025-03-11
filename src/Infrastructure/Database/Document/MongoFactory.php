<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Database\Document;

use MongoDB\Collection;

interface MongoFactory
{
    public function make(string $resource): Collection;
}
