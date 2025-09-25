<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository;

use ReflectionException;
use Serendipity\Domain\Exception\ManagedException;
use Serendipity\Infrastructure\Database\Managed;
use Serendipity\Infrastructure\Database\Relational\Connection;
use Serendipity\Infrastructure\Database\Relational\ConnectionFactory;
use Serendipity\Infrastructure\Repository\Adapter\RelationalDeserializerFactory;
use Serendipity\Infrastructure\Repository\Adapter\RelationalSerializerFactory;

abstract class PostgresRepository extends Repository
{
    protected readonly Connection $database;

    protected string $connection = 'postgres';

    public function __construct(
        protected readonly Managed $managed,
        protected readonly RelationalDeserializerFactory $deserializerFactory,
        protected readonly RelationalSerializerFactory $serializerFactory,
        ConnectionFactory $connectionFactory,
    ) {
        $this->database = $connectionFactory->make($this->connection);
    }

    /**
     * @param array<string> $fields
     * @param array<string,mixed> $default
     * @param array<string,string> $managed
     * @throws ManagedException
     * @throws ReflectionException
     */
    protected function bindings(
        object $instance,
        array $fields,
        array $default = [],
        array $managed = [
            'created_at' => 'timestamp',
            'updated_at' => 'timestamp',
        ],
    ): array {
        $values = $this->deserializerFactory->make($instance::class)
            ->deserialize($instance);

        $values = array_merge($default, $values);

        foreach ($managed as $field => $type) {
            $value = match ($type) {
                'timestamp' => $this->managed->now(),
                'id' => $this->managed->id(),
                default => throw new ManagedException($type)
            };
            $values[$field] = $value;
        }
        $callback = function (string $field) use ($values) {
            $element = $values[$field] ?? null;
            return match (true) {
                is_scalar($element) => $element,
                default => json_encode($element),
            };
        };
        return array_map($callback, $fields);
    }

    /**
     * @param array<string> $fields
     */
    protected function columns(array $fields): string
    {
        return implode(', ', array_map(fn ($field) => sprintf('"%s"', $field), $fields));
    }

    /**
     * @param array<string> $fields
     */
    protected function wildcards(array $fields): string
    {
        return implode(', ', array_fill(0, count($fields), '?'));
    }
}
