<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Database\Document\Mongo;

use Serendipity\Infrastructure\Database\Document\Mongo\Condition\BetweenCondition;
use Serendipity\Infrastructure\Database\Document\Mongo\Condition\EqualCondition;
use Serendipity\Infrastructure\Database\Document\Mongo\Condition\InCondition;
use Serendipity\Infrastructure\Database\Document\Mongo\Condition\RegexCondition;

use function Constructo\Cast\arrayify;
use function Constructo\Cast\stringify;
use function Constructo\Util\extractString;

final class Search extends SearchEngine
{
    /**
     * @param array<string,Condition|string> $conditions
     */
    public static function create(
        array $conditions = [
            'between' => BetweenCondition::class,
            'equal' => EqualCondition::class,
            'in' => InCondition::class,
            'regex' => RegexCondition::class,
        ],
    ): self {
        return new self(new ConditionParser($conditions));
    }

    public function make(string $field, mixed $value): string
    {
        $value = stringify($value);
        $array = [
            $field,
            sprintf('"%s"', $value),
        ];
        return implode(self::SEARCH_SEPARATOR, $array);
    }

    public function unmake(string $filter): array
    {
        $pieces = explode(self::SEARCH_SEPARATOR, $filter, 2);
        if (count($pieces) === 2) {
            return [$pieces[0] => $this->removeQuotes($pieces[1])];
        }
        return [$pieces[0] => null];
    }

    public function parse(string $expression): array
    {
        $matches = $this->match($expression);

        $stack = [];
        $parsed = [];
        foreach ($matches as $match) {
            $match = arrayify($match);
            if ($this->isKeyValue($match)) {
                $key = extractString($match, 'key');
                $value = extractString($match, 'value');
                $parsed = $this->mergeCondition($parsed, $key, $value);
                continue;
            }
            if (! empty($match['operator'])) {
                $parsed = $this->wrapOperator($parsed, extractString($match, 'operator'));
                continue;
            }
            $this->handleGrouping($parsed, $stack, $match);
        }
        return $parsed;
    }
}
