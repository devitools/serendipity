<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Infrastructure\Repository\SleekDB;

use ReflectionException;
use Serendipity\Domain\Contract\Adapter\Deserializer;
use Serendipity\Domain\Exception\ManagedException;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Domain\Repository\GameCommandRepository;
use Serendipity\Infrastructure\Adapter\DeserializerFactory;
use Serendipity\Infrastructure\Database\Document\SleekDBFactory;
use Serendipity\Infrastructure\Database\Managed;
use SleekDB\Exceptions\IdNotAllowedException;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;
use SleekDB\Exceptions\JsonException;

/**
 * @extends SleekDBGameRepository<GameCommand>
 */
class SleekDBGameCommandRepository extends SleekDBGameRepository implements GameCommandRepository
{
    /**
     * @var Deserializer<GameCommand>
     */
    protected readonly Deserializer $deserializer;

    public function __construct(
        Managed $managed,
        SleekDBFactory $storeFactory,
        DeserializerFactory $deserializerFactory,
    ) {
        parent::__construct($managed, $storeFactory);

        $this->deserializer = $deserializerFactory->make(GameCommand::class);
    }

    /**
     * @throws IOException
     * @throws JsonException
     * @throws IdNotAllowedException
     * @throws InvalidArgumentException
     * @throws ManagedException
     * @throws ReflectionException
     */
    public function create(GameCommand $game): string
    {
        $datum = $this->deserializer->deserialize($game);
        $id = $this->managed->id();
        $datum['id'] = $id;
        $datum['created_at'] = $this->managed->now();
        $datum['updated_at'] = $this->managed->now();
        $this->store->insert($datum);
        return $id;
    }

    /**
     * @throws InvalidArgumentException
     * @throws IOException
     */
    public function delete(string $id): bool
    {
        return (bool) $this->store->deleteBy(['id', '=', $id]);
    }
}
