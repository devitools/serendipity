<?php

declare(strict_types=1);

namespace Serendipity\Domain\Support\Reflective\Factory\Ruler;

use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Serendipity\Domain\Support\Reflective\Attribute\Define;
use Serendipity\Domain\Support\Reflective\Attribute\Pattern;
use Serendipity\Domain\Support\Reflective\Definition\Type;
use Serendipity\Domain\Support\Reflective\Definition\TypeExtended;
use Serendipity\Domain\Support\Reflective\Factory\Chain;
use Serendipity\Domain\Support\Reflective\Ruleset;

use function Serendipity\Notation\snakify;
use function Serendipity\Type\Cast\boolify;

class AttributeChain extends Chain
{
    public function resolve(ReflectionParameter $parameter, Ruleset $rules): Ruleset
    {
        $field = $this->dottedField($parameter);
        $type = $parameter->getType();

        $attributes = $parameter->getAttributes();
        foreach ($attributes as $attribute) {
            $this->resolveAttribute($attribute, $type, $field, $rules);
        }

        return parent::resolve($parameter, $rules);
    }

    private function resolveAttribute(
        ReflectionAttribute $attribute,
        ?ReflectionType $type,
        string $field,
        Ruleset $rules
    ): void {
        $instance = $attribute->newInstance();
        match (true) {
            $instance instanceof Pattern => $this->resolveAttributePattern($instance, $type, $field, $rules),
            $instance instanceof Define => $this->resolveAttributeDefine($instance, $field, $rules),
            default => null,
        };
    }

    private function resolveAttributePattern(
        Pattern $instance,
        ?ReflectionType $type,
        string $field,
        Ruleset $rules
    ): ?bool {
        return match (true) {
            $type instanceof ReflectionNamedType && $type->isBuiltin() => match ($type->getName()) {
                'string', 'int', 'float' => $rules->add($field, 'regex', $instance->pattern),
                default => null,
            },
            $type instanceof ReflectionUnionType => $this->resolveAttributePatternUnion(
                $instance,
                $field,
                $rules,
                $type
            ),
            default => null,
        };
    }

    private function resolveAttributePatternUnion(
        Pattern $instance,
        string $field,
        Ruleset $rules,
        ReflectionUnionType $type
    ): bool {
        $done = false;
        foreach ($type->getTypes() as $unionType) {
            $done = $this->resolveAttributePattern($instance, $unionType, $field, $rules);
            if ($done) {
                break;
            }
        }
        return boolify($done);
    }

    private function resolveAttributeDefine(
        Define $instance,
        string $field,
        Ruleset $rules
    ): void {
        foreach ($instance->types as $type) {
            $this->resolveAttributeDefineType($type, $rules, $field);
        }
    }

    private function resolveAttributeDefineType(Type|TypeExtended $type, Ruleset $rules, string $field): void
    {
        if ($type instanceof TypeExtended) {
            return;
        }
        $rules->add($field, snakify($type->name));
    }
}
