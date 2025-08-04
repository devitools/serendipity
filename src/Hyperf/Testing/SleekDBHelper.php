<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Testing;

use Constructo\Core\Fake\Faker;
use Constructo\Support\Set;
use ReflectionException;
use Serendipity\Infrastructure\Adapter\DeserializerFactory;
use Serendipity\Infrastructure\Adapter\SerializerFactory;
use Serendipity\Infrastructure\Database\Document\SleekDBFactory;
use Serendipity\Testing\Resource\AbstractHelper;
use SleekDB\Exceptions\IdNotAllowedException;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;
use SleekDB\Exceptions\JsonException as SleekDBJsonExceptionAlias;

use function Serendipity\Type\Cast\arrayify;

final class SleekDBHelper extends AbstractHelper
{
    public function __construct(
        Faker $faker,
        SerializerFactory $serializerFactory,
        DeserializerFactory $deserializerFactory,
        private readonly SleekDBFactory $factory,
    ) {
        parent::__construct($faker, $serializerFactory, $deserializerFactory);
    }

    /**
     * @throws IOException
     * @throws InvalidArgumentException
     */
    public function truncate(string $resource): void
    {
        $database = $this->factory->make($resource);
        $database->deleteBy(
            [
                '_id',
                '>=',
                0,
            ]
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $type
     * @throws ReflectionException
     * @throws IOException
     * @throws IdNotAllowedException
     * @throws InvalidArgumentException
     * @throws SleekDBJsonExceptionAlias
     */
    public function seed(string $type, string $resource, array $override = []): Set
    {
        $data = $this->fake($type, $override);

        $generated = $this->factory->make($resource)
            ->insert($data);
        return new Set(array_merge($data, $generated));
    }

    /**
     * @throws IOException
     * @throws InvalidArgumentException
     */
    public function count(string $resource, array $filters = []): int
    {
        $database = $this->factory->make($resource);
        $array = arrayify($database->findBy($filters));
        return count($array);
    }
}
