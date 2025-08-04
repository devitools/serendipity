<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Infrastructure\Repository\Mongo;

use DateMalformedStringException;
use ReflectionException;
use Serendipity\Domain\Contract\Adapter\Deserializer;
use Serendipity\Domain\Exception\ManagedException;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Domain\Repository\GameCommandRepository;
use Serendipity\Infrastructure\Database\Document\MongoFactory;
use Serendipity\Infrastructure\Database\Managed;
use Serendipity\Infrastructure\Repository\Adapter\MongoDeserializerFactory;

class MongoGameCommandRepository extends MongoGameRepository implements GameCommandRepository
{
    /*
     * @var Deserializer<GameCommand>
     */
    protected readonly Deserializer $deserializer;

    public function __construct(
        Managed $managed,
        MongoFactory $mongoFactory,
        MongoDeserializerFactory $deserializerFactory,
    ) {
        parent::__construct($managed, $mongoFactory);

        $this->deserializer = $deserializerFactory->make(GameCommand::class);
    }

    /**
     * @throws ManagedException
     * @throws DateMalformedStringException
     * @throws ReflectionException
     */
    public function create(GameCommand $game): string
    {
        $datum = $this->deserializer->deserialize($game);
        $id = $this->managed->id();
        $datum['id'] = $id;
        $datum['created_at'] = $this->toDateTime($this->managed->now());
        $datum['updated_at'] = $this->toDateTime($this->managed->now());
        $this->collection->insertOne($datum);
        return $id;
    }

    public function delete(string $id): bool
    {
        return (bool) $this->collection->deleteOne(['id' => $id]);
    }
}
