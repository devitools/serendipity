<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Infrastructure\Repository\Postgres;

use Exception;
use Hyperf\DB\Exception\QueryException;
use Serendipity\Domain\Exception\ManagedException;
use Serendipity\Domain\Exception\UniqueKeyViolationException;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Domain\Repository\GameCommandRepository;
use Serendipity\Hyperf\Database\Relational\Support\HasPostgresUniqueConstraint;
use Serendipity\Infrastructure\Repository\PostgresRepository;

class PostgresGameCommandRepository extends PostgresRepository implements GameCommandRepository
{
    use HasPostgresUniqueConstraint;

    /**
     * @throws ManagedException
     * @throws Exception|UniqueKeyViolationException
     */
    public function create(GameCommand $game): string
    {
        $id = $this->managed->id();
        $fields = [
            'id',
            'created_at',
            'updated_at',
            'name',
            'slug',
            'published_at',
            'data',
            'features',
        ];
        /* @noinspection SqlNoDataSourceInspection, SqlResolve */
        $query = 'insert into "games"'
            . ' ("id", "created_at", "updated_at", "name", "slug", "published_at", "data", "features") '
            . 'values (?, ?, ?, ?, ?, ?, ?, ?)';


        $bindings = $this->bindings($game, $fields, ['id' => $id]);
        try {
            $this->database->execute($query, $bindings);
        } catch (QueryException $exception) {
            $detected = $this->detectUniqueKeyViolation($exception);
            throw $detected ?? $exception;
        }
        return $id;
    }

    public function delete(string $id): bool
    {
        /* @noinspection SqlNoDataSourceInspection, SqlResolve */
        $query = 'delete from "games" where "id" = ?';
        $affected = $this->database->execute($query, [$id]);
        return $affected > 0;
    }
}
