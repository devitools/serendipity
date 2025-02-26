<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Adapter\Serializing\Serialize\Resolve;

use Serendipity\Domain\Exception\Mapping\NotResolved;
use Serendipity\Domain\Exception\Mapping\NotResolvedType;
use Serendipity\Domain\Support\Value;
use Serendipity\Domain\Support\Values;
use ReflectionException;
use ReflectionParameter;

class CheckRequiredChain extends Chain
{
    /**
     * @throws ReflectionException
     */
    public function resolve(ReflectionParameter $parameter, Values $values): Value
    {
        $name = $this->normalize($parameter);
        if ($values->has($name)) {
            return parent::resolve($parameter, $values);
        }
        return $this->makeValues($parameter, $name);
    }

    /**
     * @throws ReflectionException
     */
    public function makeValues(ReflectionParameter $parameter, string $name): Value
    {
        if ($parameter->isOptional() || $parameter->isDefaultValueAvailable()) {
            return new Value($parameter->getDefaultValue());
        }
        if ($parameter->allowsNull()) {
            return new Value(null);
        }
        return new Value(new NotResolved(NotResolvedType::REQUIRED, $name));
    }
}
