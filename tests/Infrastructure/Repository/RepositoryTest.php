<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository;

use Constructo\Support\Datum;
use Constructo\Support\Entity;
use Constructo\Type\Collection;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Serendipity\Domain\Contract\Adapter\Serializer;
use Serendipity\Infrastructure\Repository\Repository;

final class RepositoryTest extends TestCase
{
    public function testShouldRenderEntity(): void
    {
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('serialize')
            ->willReturnCallback(fn (array $datum) => new class ($datum['id']) extends Entity {
                public function __construct(public readonly int $id)
                {
                }
            });

        $repository = new class ($serializer) extends Repository {
            public function __construct(private readonly Serializer $serializer)
            {
            }

            public function getEntity(int $id): Entity
            {
                return $this->entity($this->serializer, [['id' => $id]]);
            }
        };

        $entity = $repository->getEntity(1);
        $this->assertEquals(1, $entity->id);
    }

    public function testShouldRenderCollection(): void
    {
        $serializer = $this->createMock(Serializer::class);
        $serializer->method('serialize')
            ->willReturnCallback(fn (array $datum) => match ($datum['type']) {
                'entity' => new class extends Entity {
                    public function __construct(public readonly int $id = 1)
                    {
                    }
                },
                default => throw new RuntimeException(),
            });

        $collection = new class extends Collection {
            protected function validate(mixed $datum): Datum|Entity
            {
                return $datum instanceof Entity || $datum instanceof Datum
                    ? $datum
                    : throw new RuntimeException();
            }

            public function current(): Datum|Entity
            {
                return $this->validate($this->datum());
            }
        };

        $repository = new class ($serializer, $collection) extends Repository {
            public function __construct(
                private readonly Serializer $serializer,
                private readonly Collection $collection,
            ) {
            }

            public function all(): Collection
            {
                $data = [
                    ['type' => 'datum'],
                    ['type' => 'entity'],
                ];
                return $this->collection($this->serializer, $data, $this->collection::class);
            }
        };
        $collection = $repository->all();
        $this->assertInstanceof(Datum::class, $collection->current());
    }
}
