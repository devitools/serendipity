<?php

declare(strict_types=1);

namespace Serendipity\Domain\Support\Reflective\Engine;

use Constructo\Contract\Formatter;
use Constructo\Support\Reflective\Notation;
use Constructo\Support\Value;
use ReflectionParameter;
use Serendipity\Domain\Exception\Adapter\NotResolved;
use Serendipity\Domain\Exception\Adapter\NotResolvedCollection;

use function is_array;
use function Serendipity\Notation\format;
use function Serendipity\Type\Cast\stringify;
use function sprintf;

abstract class Resolution
{
    public function __construct(
        public readonly Notation $notation = Notation::SNAKE,
        /**
         * @var array<callable|Formatter>
         */
        public readonly array $formatters = [],
        /**
         * @var array<string>
         */
        protected readonly array $path = [],
    ) {
    }

    protected function casedField(ReflectionParameter|string $parameter): string
    {
        $name = is_string($parameter) ? $parameter : $parameter->getName();
        return format($name, $this->notation);
    }

    protected function dottedField(null|ReflectionParameter|string $parameter = null): string
    {
        $last = $parameter === null ? [] : [$this->casedField($parameter)];
        $pieces = [
            ...$this->path,
            ...$last,
        ];
        return stringify(implode('.', $pieces));
    }

    /**
     * @param array<NotResolved>|string $unresolved
     */
    protected function notResolved(array|string $unresolved, mixed $value = null): Value
    {
        if (is_array($unresolved)) {
            return new Value(new NotResolvedCollection($unresolved, $this->path, $value));
        }
        $field = $this->dottedField();
        return new Value(new NotResolved($unresolved, $field, $value));
    }

    protected function notResolvedAsRequired(): Value
    {
        $field = $this->dottedField();
        $message = sprintf("The value for '%s' is required and was not given.", $field);
        return $this->notResolved($message);
    }

    protected function notResolvedAsInvalid(mixed $value): Value
    {
        $field = $this->dottedField();
        $message = sprintf("The value given for '%s' is not supported.", $field);
        return $this->notResolved($message, $value);
    }

    protected function notResolvedAsTypeMismatch(string $expected, string $actual, mixed $value): Value
    {
        $field = $this->dottedField();
        $message = sprintf(
            "The value for '%s' must be of type '%s' and '%s' was given.",
            $field,
            $expected,
            $actual,
        );
        return $this->notResolved($message, $value);
    }
}
