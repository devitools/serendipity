<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Testing;

use Constructo\Core\Fake\Faker;
use Constructo\Support\Set;
use ReflectionException;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionFactory;
use Serendipity\Infrastructure\Database\Relational\Connection;
use Serendipity\Infrastructure\Repository\Adapter\RelationalDeserializerFactory;
use Serendipity\Infrastructure\Repository\Adapter\RelationalSerializerFactory;
use Serendipity\Testing\Resource\AbstractHelper;

use function array_filter;
use function array_keys;
use function array_map;
use function array_shift;
use function array_values;
use function Constructo\Cast\arrayify;
use function Constructo\Cast\integerify;
use function Constructo\Json\encode;
use function count;
use function implode;
use function sprintf;
use function str_repeat;

final class PostgresHelper extends AbstractHelper
{
    private readonly Connection $database;

    public function __construct(
        Faker $faker,
        RelationalSerializerFactory $serializerFactory,
        RelationalDeserializerFactory $deserializerFactory,
        HyperfConnectionFactory $hyperfDatabaseFactory,
        ?string $connection = null,
    ) {
        parent::__construct($faker, $serializerFactory, $deserializerFactory);

        $this->database = $hyperfDatabaseFactory->make($connection ?? 'postgres');
    }

    public function truncate(string $resource): void
    {
        /* @noinspection SqlNoDataSourceInspection */
        $this->database->execute(sprintf('delete from %s where true', $resource));
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @throws ReflectionException
     */
    public function seed(string $type, string $resource, array $override = []): Set
    {
        $data = $this->fake($type, $override);
        $fields = array_keys($data);
        $fields = array_map(fn (string $field) => sprintf('"%s"', $field), $fields);
        $columns = implode(',', $fields);
        $values = str_repeat('?,', count($fields) - 1) . '?';

        /** @noinspection SqlNoDataSourceInspection, SqlResolve */
        $query = sprintf('insert into "%s" (%s) values (%s)', $resource, $columns, $values);
        $bindings = array_map($this->normalize(...), array_values($data));

        $this->database->execute($query, $bindings);
        return new Set($data);
    }

    public function count(string $resource, array $filters): int
    {
        $callback = fn (string $key, mixed $value): string => ($value === null)
            ? sprintf('"%s" is null', $key)
            : sprintf('"%s" = ?', $key);
        $wildcards = array_map($callback, array_keys($filters), array_values($filters));
        $where = implode(' and ', $wildcards);
        /** @noinspection SqlNoDataSourceInspection */
        $query = sprintf('select count(*) as count from "%s" where %s', $resource, $where);
        $bindings = array_values(array_filter($filters, fn (mixed $value) => $value !== null));
        $bindings = array_map($this->normalize(...), $bindings);
        $result = $this->database->query($query, $bindings);
        $array = arrayify($result);
        $data = arrayify(array_shift($array));
        if (empty($data)) {
            return 0;
        }
        $count = $data['count'] ?? 0;
        return integerify($count);
    }

    private function normalize(mixed $element): null|float|int|string
    {
        return match (true) {
            is_bool($element) => integerify($element),
            is_scalar($element) => $element,
            default => encode(arrayify($element)),
        };
    }
}
