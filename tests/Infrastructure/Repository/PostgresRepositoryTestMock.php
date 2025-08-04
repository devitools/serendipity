<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository;

use Serendipity\Infrastructure\Repository\PostgresRepository;

class PostgresRepositoryTestMock extends PostgresRepository
{
    public function exposeBindings(
        object $instance,
        array $fields,
        array $default = [],
        array $generate = [
            'created_at' => 'timestamp',
            'updated_at' => 'timestamp',
        ],
    ): array {
        return $this->bindings($instance, $fields, $default, $generate);
    }

    public function exposeColumns(array $fields): string
    {
        return $this->columns($fields);
    }

    public function exposeWildcards(array $fields): string
    {
        return $this->wildcards($fields);
    }
}
