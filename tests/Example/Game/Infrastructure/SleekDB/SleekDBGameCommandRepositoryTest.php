<?php

declare(strict_types=1);

namespace Serendipity\Test\Example\Game\Infrastructure\SleekDB;

use Constructo\Testing\BuilderExtension;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Infrastructure\Repository\SleekDB\SleekDBGameCommandRepository;
use Serendipity\Test\Example\Game\InfrastructureCase;

class SleekDBGameCommandRepositoryTest extends InfrastructureCase
{
    use BuilderExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpResource('games', 'sleek');
    }

    public function testShouldPersistSuccessfully(): void
    {
        $repository = $this->make(SleekDBGameCommandRepository::class);
        $values = $this->faker()
            ->fake(GameCommand::class);
        $game = $this->builder()
            ->build(GameCommand::class, $values);
        $id = $repository->create($game);

        $this->assertHas(
            [
                [
                    'id',
                    '=',
                    $id,
                ],
            ]
        );
    }

    public function testShouldDestroySuccessfully(): void
    {
        $repository = $this->make(SleekDBGameCommandRepository::class);

        $values = $this->seed(Game::class);
        $id = $values->get('id');

        $this->assertHasExactly(
            1,
            [
                [
                    'id',
                    '=',
                    $id,
                ],
            ]
        );

        $repository->delete($id);

        $this->assertHasNot(
            [
                [
                    'id',
                    '=',
                    $id,
                ],
            ]
        );
    }
}
