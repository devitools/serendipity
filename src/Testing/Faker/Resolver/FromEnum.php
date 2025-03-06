<?php

declare(strict_types=1);

namespace Serendipity\Testing\Faker\Resolver;

use BackedEnum;
use Random\RandomException;
use ReflectionNamedType;
use ReflectionParameter;
use Serendipity\Domain\Support\Set;
use Serendipity\Domain\Support\Value;
use Serendipity\Testing\Faker\Resolver;

final class FromEnum extends Resolver
{
    /**
     * @throws RandomException
     */
    public function resolve(ReflectionParameter $parameter, Set $presets): ?Value
    {
        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType) {
            return parent::resolve($parameter, $presets);
        }
        $enum = $type->getName();
        return enum_exists($enum)
            ? $this->resolveEnumValue($enum, $parameter, $presets)
            : parent::resolve($parameter, $presets);
    }

    /**
     * @throws RandomException
     */
    private function resolveEnumValue(string $enum, ReflectionParameter $parameter, Set $presets): ?Value
    {
        if (! is_subclass_of($enum, BackedEnum::class)) {
            return parent::resolve($parameter, $presets);
        }

        $enumValues = $enum::cases();
        if (empty($enumValues)) {
            return parent::resolve($parameter, $presets);
        }

        $randomValue = $enumValues[random_int(0, count($enumValues) - 1)]->value;
        return new Value($randomValue);
    }
}
