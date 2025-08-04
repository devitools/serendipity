<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Adapter\Support;

use Constructo\Support\Value;
use DateMalformedStringException;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Serendipity\Domain\Support\Reflective\Attribute\Define;
use Serendipity\Domain\Support\Reflective\Attribute\Managed;
use Serendipity\Domain\Support\Reflective\Attribute\Pattern;
use Serendipity\Domain\Support\Reflective\Definition\Type;
use Serendipity\Domain\Support\Reflective\Definition\TypeExtended;

trait AttributeAdapter
{
    abstract protected function resolveManaged(Managed $instance, mixed $value): ?Value;

    abstract protected function resolveDefineType(Type $type, mixed $value): Value;

    abstract protected function resolveDefineTypeExtended(TypeExtended $type, mixed $value): Value;

    abstract protected function resolvePatternFromNamedType(
        Pattern $instance,
        mixed $value,
        ReflectionNamedType $type
    ): ?Value;

    /**
     * @throws DateMalformedStringException
     */
    protected function resolveByAttributes(ReflectionParameter $parameter, mixed $value): ?Value
    {
        $attributes = $parameter->getAttributes();
        if (empty($attributes)) {
            return null;
        }
        $resolved = null;
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $resolved = match (true) {
                $instance instanceof Managed => $this->resolveManaged($instance, $value),
                $instance instanceof Define => $this->resolveDefine($instance, $value),
                $instance instanceof Pattern => $this->resolvePattern($instance, $value, $parameter->getType()),
                default => null,
            };
            if ($resolved !== null) {
                break;
            }
        }
        return $resolved;
    }

    protected function resolvePattern(Pattern $instance, mixed $value, ?ReflectionType $type): ?Value
    {
        return match (true) {
            $type instanceof ReflectionNamedType => $this->resolvePatternFromNamedType($instance, $value, $type),
            $type instanceof ReflectionUnionType => $this->resolvePatternFromUnionType($instance, $value, $type),
            default => null,
        };
    }

    protected function resolvePatternFromUnionType(
        Pattern $instance,
        mixed $value,
        ReflectionUnionType $unionType
    ): ?Value {
        $types = $unionType->getTypes();
        $resolved = null;
        foreach ($types as $type) {
            $resolved = $this->resolvePattern($instance, $value, $type);
            if ($resolved !== null) {
                break;
            }
        }
        return $resolved;
    }

    protected function resolveDefine(Define $instance, mixed $value): ?Value
    {
        $types = $instance->types;
        $callback = fn (?Value $carry, Type|TypeExtended $type): Value => $carry ?? match (true) {
            $type instanceof Type => $this->resolveDefineType($type, $value),
            $type instanceof TypeExtended => $this->resolveDefineTypeExtended($type, $value),
        };
        return array_reduce($types, $callback);
    }
}
