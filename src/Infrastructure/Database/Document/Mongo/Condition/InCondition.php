<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Database\Document\Mongo\Condition;

use Serendipity\Infrastructure\Database\Document\Mongo\Condition;

class InCondition implements Condition
{
    public function compose(string $value): array
    {
        $pieces = array_map(trim(...), explode(',', $value));
        return [
            '$in' => $pieces,
        ];
    }
}
