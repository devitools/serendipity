<?php

declare(strict_types=1);

namespace Serendipity\Presentation;

use Constructo\Factory\ReflectorFactory;
use Constructo\Factory\SchemaFactory;
use Constructo\Support\Metadata\Schema;
use Constructo\Support\Set;
use Psr\Container\ContainerInterface;
use ReflectionException;

abstract class ReflectorInput extends Input
{
    protected readonly Schema $schema;

    protected ?string $source = null;

    /**
     * @param array<string, array|string> $rules
     * @param array<string, callable(mixed):mixed|string> $mappings
     * @throws ReflectionException
     */
    public function __construct(
        protected readonly SchemaFactory $schemaFactory,
        ReflectorFactory $factory,
        ContainerInterface $container,
        Set $properties = new Set([]),
        Set $values = new Set([]),
        array $rules = [],
        array $mappings = [],
        bool $authorize = true,
    ) {
        parent::__construct($container, $properties, $values, $rules, $mappings, $authorize);

        $this->schema = $this->setup($factory);
    }

    /**
     * @return array<string, array|string>
     */
    final public function rules(): array
    {
        return $this->schema->rules();
    }

    /**
     * @return array<string, callable(mixed):mixed|string>
     */
    final public function mappings(): array
    {
        return $this->schema->mappings();
    }

    /**
     * @throws ReflectionException
     */
    protected function setup(ReflectorFactory $factory): Schema
    {
        $schema = $this->make($factory);
        return $this->using($schema);
    }

    protected function using(Schema $schema): Schema
    {
        return $schema;
    }

    protected function fallback(array $data, string $field): ?string
    {
        return isset($data[$field])
            ? $field
            : null;
    }

    /**
     * @throws ReflectionException
     */
    private function make(ReflectorFactory $factory): Schema
    {
        if ($this->source === null || ! class_exists($this->source)) {
            return $this->schemaFactory->make();
        }
        $reflector = $factory->make();
        /** @var class-string<object> $source */
        $source = $this->source;
        return $reflector->reflect($source);
    }
}
