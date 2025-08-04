<?php

declare(strict_types=1);

namespace Serendipity\Presentation;

use Constructo\Factory\ReflectorFactory;
use Constructo\Factory\SchemaFactory;
use Constructo\Support\Cache;
use Constructo\Support\Metadata\Schema;
use Constructo\Support\Set;
use Psr\Container\ContainerInterface;
use ReflectionException;

use function is_array;

abstract class ReflectorInput extends Input
{
    protected readonly Schema $schema;

    protected ?string $source = null;

    /**
     * @throws ReflectionException
     */
    public function __construct(
        protected readonly Cache $cache,
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
        $rules = $this->cache->get('rules');
        if (is_array($rules)) {
            return $rules;
        }
        $rules = $this->schema->rules();
        return $this->cache->set('rules', $rules);
    }

    /**
     * @return array<string, callable(array $data):mixed|string>
     */
    final public function mappings(): array
    {
        $mappings = $this->cache->get('mappings');
        if (is_array($mappings)) {
            return $mappings;
        }
        $mappings = $this->schema->mappings();
        return $this->cache->set('mappings', $mappings);
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
            ?
            $field
            :
            null;
    }

    /**
     * @throws ReflectionException
     */
    private function make(ReflectorFactory $factory)
    {
        if ($this->source === null) {
            return $this->schemaFactory->make();
        }
        $reflector = $factory->make();
        return $reflector->reflect($this->source);
    }
}
