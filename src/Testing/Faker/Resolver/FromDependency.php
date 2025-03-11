<?php

declare(strict_types=1);

namespace Serendipity\Testing\Faker\Resolver;

use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Serendipity\Domain\Support\Set;
use Serendipity\Domain\Support\Value;
use Serendipity\Testing\Faker\Resolver;

final class FromDependency extends Resolver
{
    /**
     * @throws ReflectionException
     */
    public function resolve(ReflectionParameter $parameter, Set $presets): ?Value
    {
        $type = $parameter->getType();
        if ($type instanceof ReflectionIntersectionType) {
            return null;
        }

        return $this->resolveReflectionParameterType($type, $presets)
            ?? parent::resolve($parameter, $presets);
    }

    /**
     * @throws ReflectionException
     */
    private function resolveReflectionParameterType(?ReflectionType $type, Set $presets): ?Value
    {
        return match (true) {
            $type instanceof ReflectionNamedType => $this->resolveFromNamedType($type, $presets),
            $type instanceof ReflectionUnionType => $this->resolveFromUnionType($type, $presets),
            default => null,
        };
    }

    /**
     * @throws ReflectionException
     */
    private function resolveFromNamedType(ReflectionNamedType $type, Set $presets): ?Value
    {
        $class = $type->getName();
        if ($this->isEligibleForDependency($class, $type)) {
            return null;
        }
        if (! class_exists($class)) {
            return null;
        }
        $resolved = $this->fake($class, $presets->toArray());
        return new Value($resolved->toArray());
    }

    /**
     * @throws ReflectionException
     */
    private function resolveFromUnionType(ReflectionUnionType $unionType, Set $presets): ?Value
    {
        $types = $unionType->getTypes();
        $callback = fn (?Value $carry, ?ReflectionType $type): ?Value => $carry
            ?? $this->resolveReflectionParameterType($type, $presets);
        return array_reduce($types, $callback);
    }

    private function isEligibleForDependency(string $class, ReflectionNamedType $type): bool
    {
        return $type->isBuiltin() || enum_exists($class);
    }
}
