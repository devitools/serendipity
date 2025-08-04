<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Database\Document\Mongo;

use function array_unshift_key;
use function Constructo\Cast\arrayify;
use function preg_match_all;
use function sprintf;
use function trim;

abstract class SearchEngine
{
    public const string OPERATOR_AND = 'and';

    public const string OPERATOR_OR = 'or';

    public const string SEARCH_SEPARATOR = '=';

    public function __construct(private readonly ConditionParser $conditionParser)
    {
    }

    final protected function match(string $expression): array
    {
        $pattern = sprintf(
            '/(?<key>[\w.]+)%s(?<value>"[^"]*"|[^ ()]+)|(?<operator>%s|%s)|(?<grouping>[()])/',
            self::SEARCH_SEPARATOR,
            self::OPERATOR_AND,
            self::OPERATOR_OR,
        );
        preg_match_all($pattern, $expression, $matches, PREG_SET_ORDER);
        return $matches;
    }

    final protected function isKeyValue(array $match): bool
    {
        return ! empty($match['key']) && ! empty($match['value']);
    }

    final protected function mergeCondition(array $parsed, string $key, string $value): array
    {
        $value = $this->removeQuotes($value);
        $condition = $this->conditionParser->parse($key, $value);

        if (empty($parsed)) {
            return $condition;
        }
        return array_unshift_key($parsed, '$and', $condition);
    }

    final protected function wrapOperator(array $parsed, string $operator): array
    {
        $mongoOperator = '$' . $operator;
        return [$mongoOperator => [$parsed]];
    }

    final protected function handleGrouping(array &$parsed, array &$stack, array $match): void
    {
        if ($match['grouping'] === '(') {
            $stack[] = $parsed;
            $parsed = [];
            return;
        }
        if ($match['grouping'] === ')') {
            $lastGroup = array_pop($stack);
            $parsed = $this->mergeGroup(arrayify($lastGroup), $parsed);
        }
    }

    final protected function removeQuotes(string $value): string
    {
        return trim($value, '"');
    }

    private function mergeGroup(array $lastGroup, array $parsed): array
    {
        if (empty($lastGroup)) {
            return $parsed;
        }
        if (! isset($lastGroup['$and'])) {
            $lastGroup = ['$and' => [$lastGroup]];
        }
        return array_unshift_key($lastGroup, '$and', $parsed);
    }
}
