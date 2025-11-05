<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Infrastructure\Repository\Postgres;

use Serendipity\Example\Game\Domain\Collection\GameCollection;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Domain\Repository\GameQueryRepository;
use Serendipity\Infrastructure\Repository\PostgresRepository;

/**
 * @extends PostgresRepository<Game>
 */
class PostgresGameQueryRepository extends PostgresRepository implements GameQueryRepository
{
    public function getGame(string $id): ?Game
    {
        /* @noinspection SqlNoDataSourceInspection, SqlResolve */
        $query = 'select "id", "created_at", "updated_at", "name", "slug", "published_at", "data", "features" 
                    from "games" where "id" = ?';
        $bindings = [$id];
        $data = $this->database->query($query, $bindings);
        $serializer = $this->serializerFactory->make(Game::class);
        return $this->entity($serializer, $data);
    }

    public function getGames(array $filters = []): GameCollection
    {
        /* @noinspection SqlNoDataSourceInspection, SqlResolve */
        $query = 'select "id", "created_at", "updated_at", "name", "slug", "published_at", "data", "features" 
                    from "games"';
        $data = $this->database->query($query);
        $serializer = $this->serializerFactory->make(Game::class);
        return $this->collection($serializer, $data, GameCollection::class);
    }
}
