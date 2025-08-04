<?php

declare(strict_types=1);

namespace Serendipity\Domain\Support\Reflective\Factory;

use Constructo\Support\Reflective\Notation;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use ReflectionException;
use Serendipity\Domain\Support\Reflective\Engine;
use Serendipity\Domain\Support\Reflective\Factory\Ruler\AttributeChain;
use Serendipity\Domain\Support\Reflective\Factory\Ruler\MandatoryChain;
use Serendipity\Domain\Support\Reflective\Factory\Ruler\RecursiveChain;
use Serendipity\Domain\Support\Reflective\Factory\Ruler\TypeChain;
use Serendipity\Domain\Support\Reflective\Ruleset;

class Ruler extends Engine
{
    /**
     * @var array<string, string>
     */
    protected array $native = [
        'array' => 'array',
        'bool' => 'boolean',
        'int' => 'integer',
        'float' => 'numeric',
        'string' => 'string',
        DateTimeImmutable::class => 'date',
        DateTimeInterface::class => 'date',
        DateTime::class => 'date',
    ];

    /**
     * @param array<string> $path
     */
    public function __construct(Notation $case = Notation::SNAKE, array $path = [])
    {
        parent::__construct(notation: $case, path: $path);
    }

    /**
     * @template U of object
     * @param class-string<U> $class
     *
     * @throws ReflectionException
     */
    public function ruleset(string $class): Ruleset
    {
        return $this->make($class);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string> $path
     *
     * @throws ReflectionException
     */
    protected function make(string $class, Ruleset $ruleset = new Ruleset(), array $path = []): Ruleset
    {
        $target = Target::createFrom($class);
        $parameters = $target->getReflectionParameters();

        $path = [...$this->path, ...$path];
        $chain = (new RecursiveChain(case: $this->notation, path: $path))
            ->then(new AttributeChain(case: $this->notation, path: $path))
            ->then(new TypeChain(case: $this->notation, path: $path))
            ->then(new MandatoryChain(case: $this->notation, path: $path));
        foreach ($parameters as $parameter) {
            $chain->resolve($parameter, $ruleset);
        }
        return $ruleset;
    }
}
