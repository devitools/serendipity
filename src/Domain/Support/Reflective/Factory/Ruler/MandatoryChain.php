<?php

declare(strict_types=1);

namespace Serendipity\Domain\Support\Reflective\Factory\Ruler;

use ReflectionParameter;
use Serendipity\Domain\Support\Reflective\Factory\Chain;
use Serendipity\Domain\Support\Reflective\Ruleset;

class MandatoryChain extends Chain
{
    public function resolve(ReflectionParameter $parameter, Ruleset $rules): Ruleset
    {
        $field = $this->dottedField($parameter);
        $rule = match (true) {
            $parameter->isOptional(),
            $parameter->isDefaultValueAvailable() => 'sometimes',
            $parameter->allowsNull() => 'nullable',
            default => 'required',
        };
        $rules->add($field, $rule);
        return parent::resolve($parameter, $rules);
    }
}
