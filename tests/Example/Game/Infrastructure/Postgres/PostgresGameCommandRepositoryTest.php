<?php

declare(strict_types=1);

namespace Serendipity\Test\Example\Game\Infrastructure\Postgres;

use Serendipity\Domain\Exception\UniqueKeyViolationException;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Infrastructure\Repository\Postgres\PostgresGameCommandRepository;
use Serendipity\Test\Example\Game\InfrastructureCase;
use Serendipity\Testing\Extension\BuilderExtension;

final class PostgresGameCommandRepositoryTest extends InfrastructureCase
{
    use BuilderExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpResource('games', 'postgres');
    }

    public function testShouldPersistSuccessfully(): void
    {
        # ## Arrange
        // to create an instance of Repository
        $repository = $this->make(PostgresGameCommandRepository::class);
        // generate fake values for Game
        $values = $this->faker()->fake(GameCommand::class);
        // build a new instance of Game to be persisted
        $game = $this->builder()->build(GameCommand::class, $values);

        # ## Act
        // call the method that is being tested
        $id = $repository->create($game);

        # ## Assert
        // check if there is a record on a database with the same ID
        $this->assertHas(['id' => $id]);
    }

    public function testShouldRaiseUniqueKeyViolationExceptionOnDuplicateKey(): void
    {
        # ## Assert
        $this->expectException(UniqueKeyViolationException::class);

        # ## Arrange
        $repository = $this->make(PostgresGameCommandRepository::class);
        $values1 = $this->faker()->fake(GameCommand::class);
        $game1 = $this->builder()->build(GameCommand::class, $values1);
        $values2 = $this->faker()->fake(GameCommand::class, ['slug' => $values1->get('slug')]);
        $game2 = $this->builder()->build(GameCommand::class, $values2);

        # ## Act
        // call the same method twice to force the duplicity
        $repository->create($game1);
        $repository->create($game2);
    }

    public function testShouldDestroySuccessfully(): void
    {
        $values = $this->seed(Game::class);
        $id = $values->get('id');
        $repository = $this->make(PostgresGameCommandRepository::class);
        $repository->delete($id);
        $this->assertHasNot(['id' => $id]);
    }
}
