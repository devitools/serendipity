<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational;

use Closure;
use Constructo\Support\Set;
use Hyperf\DB\DB as Database;
use Serendipity\Infrastructure\Database\Relational\Connection;

use function get_object_vars;
use function is_object;
use function Serendipity\Type\Cast\arrayify;
use function Serendipity\Type\Cast\integerify;

class HyperfConnection implements Connection
{
    public function __construct(
        public readonly string $connection,
        private readonly Database $database
    ) {
    }

    /**
     * Open transaction (Support transaction nesting).
     */
    public function beginTransaction(): void
    {
        $this->database->beginTransaction();
    }

    /**
     * Commit transaction (Support transaction nesting).
     */
    public function commit(): void
    {
        $this->database->commit();
    }

    /**
     * Rollback transaction (Support transaction nesting).
     */
    public function rollback(): void
    {
        $this->database->rollback();
    }

    /**
     * Insert data, return the primary key ID, non-auto-incrementing primary key returns 0.
     */
    public function insert(string $query, array $bindings = []): int
    {
        return integerify($this->database->insert($query, $bindings));
    }

    /**
     * Execute SQL to return the number of rows affected.
     */
    public function execute(string $query, array $bindings = []): int
    {
        return integerify($this->database->execute($query, $bindings));
    }

    /**
     * Query SQL, return a list of result sets.
     */
    public function query(string $query, array $bindings = []): array
    {
        return arrayify($this->database->query($query, $bindings));
    }

    /**
     * Query SQL to return the first row of the result set.
     */
    public function fetch(string $query, array $bindings = []): Set
    {
        $data = $this->database->fetch($query, $bindings);
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        return Set::createFrom(arrayify($data));
    }

    /**
     * Specify the database to connect to.
     */
    public function run(Closure $closure): void
    {
        $this->database->run($closure);
    }
}
