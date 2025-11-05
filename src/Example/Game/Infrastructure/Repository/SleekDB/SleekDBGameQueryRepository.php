<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Infrastructure\Repository\SleekDB;

use Serendipity\Example\Game\Domain\Collection\GameCollection;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Domain\Repository\GameQueryRepository;
use Serendipity\Infrastructure\Adapter\SerializerFactory;
use Serendipity\Infrastructure\Database\Document\SleekDBFactory;
use Serendipity\Infrastructure\Database\Managed;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

use function Constructo\Cast\arrayify;

/**
 * @extends SleekDBGameRepository<Game>
 */
class SleekDBGameQueryRepository extends SleekDBGameRepository implements GameQueryRepository
{
    public function __construct(
        Managed $managed,
        SleekDBFactory $storeFactory,
        protected readonly SerializerFactory $serializerFactory,
    ) {
        parent::__construct($managed, $storeFactory);
    }

    /**
     * @throws InvalidArgumentException
     * @throws IOException
     */
    public function getGame(string $id): ?Game
    {
        $data = arrayify(
            $this->store->findBy(
                [
                    'id',
                    '=',
                    $id,
                ]
            )
        );
        $serializer = $this->serializerFactory->make(Game::class);
        return $this->entity($serializer, $data);
    }

    /**
     * @throws IOException
     * @throws InvalidArgumentException
     */
    public function getGames(array $filters = []): GameCollection
    {
        $serializer = $this->serializerFactory->make(Game::class);
        if (empty($filters)) {
            $data = arrayify($this->store->findAll());
            return $this->collection($serializer, $data, GameCollection::class);
        }
        $criteria = [];
        foreach ($filters as $key => $value) {
            $criteria[] = [
                $key,
                '=',
                $value,
            ];
        }
        $data = arrayify($this->store->findBy($criteria));
        return $this->collection($serializer, $data, GameCollection::class);
    }
}
