<?php

declare(strict_types=1);

namespace Serendipity\Test\Example\Game\Infrastructure\Mongo;

use Constructo\Testing\BuilderExtension;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Infrastructure\Repository\Mongo\MongoGameCommandRepository;
use Serendipity\Test\Example\Game\InfrastructureCase;

class MongoGameCommandRepositoryTest extends InfrastructureCase
{
    use BuilderExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpResource('games', 'mongo');
    }

    public function testShouldPersistSuccessfully(): void
    {
        $repository = $this->make(MongoGameCommandRepository::class);
        $presets = [
            'data' => $this->generator()
                ->words(),
        ];
        $values = $this->faker()
            ->fake(GameCommand::class, $presets);
        $game = $this->builder()
            ->build(GameCommand::class, $values);
        $id = $repository->create($game);

        $this->assertHas(['id' => $id]);
    }

    public function testShouldDestroySuccessfully(): void
    {
        $repository = $this->make(MongoGameCommandRepository::class);

        $values = $this->seed(Game::class);
        $id = $values->get('id');

        $this->assertHasExactly(1, ['id' => $id]);

        $repository->delete($id);

        $this->assertHasNot(['id' => $id]);
    }
}
