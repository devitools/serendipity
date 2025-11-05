<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Database\Document\Mongo\Condition;

use Serendipity\Infrastructure\Database\Document\Mongo\Condition;

class RegexCondition implements Condition
{
    public function compose(string $value): array
    {
        $patterns = array_map(trim(...), explode(',', $value));
        if (count($patterns) > 1) {
            return [
                '$or' => array_map(fn ($pattern) => ['$regex' => $pattern], $patterns),
            ];
        }
        return [
            '$regex' => $patterns[0],
        ];
    }
}
