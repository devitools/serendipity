<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Database\Document\Mongo;

use Serendipity\Domain\Exception\Misconfiguration;

class ConditionParser
{
    public function __construct(
        /**
         * @var array<string,Condition|string>
         */
        private array $conditions = [],
    ) {
    }

    /**
     * @throws Misconfiguration
     */
    public function parse(string $key, string $content): array
    {
        $content = trim($content, '"');
        $pieces = explode(':', $content, 2);

        if (count($pieces) === 1) {
            return [$key => $content];
        }

        [
            $alias,
            $value,
        ] = $pieces;
        $negated = false;
        if (str_starts_with($alias, '!')) {
            $negated = true;
            $alias = ltrim($alias, '!');
        }
        $condition = $this->condition($alias);
        $filter = $condition->compose($value);
        if ($negated) {
            return [
                $key => ['$not' => $filter],
            ];
        }
        return [$key => $filter];
    }

    /**
     * @throws Misconfiguration
     */
    private function condition(string $alias): Condition
    {
        $condition = $this->conditions[$alias] ?? null;
        if ($condition instanceof Condition) {
            return $condition;
        }

        $condition = $this->instance($condition, $alias);
        $this->conditions[$alias] = $condition;
        return $condition;
    }

    /**
     * @throws Misconfiguration
     */
    private function instance(mixed $condition, string $alias): Condition
    {
        if (! is_string($condition) || ! class_exists($condition)) {
            throw new Misconfiguration(sprintf("Condition '%s' not found", $alias));
        }
        $instance = new $condition();
        if (! $instance instanceof Condition) {
            throw new Misconfiguration(
                sprintf("Condition should be an instance of '%s'", Condition::class)
            );
        }
        return $instance;
    }
}
