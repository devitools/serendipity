<?php

declare(strict_types=1);

namespace Serendipity\Test\Example\Game\Infrastructure\Mongo;

use Constructo\Testing\ManagedExtension;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Infrastructure\Repository\Mongo\MongoGameQueryRepository;
use Serendipity\Test\Example\Game\InfrastructureCase;

use function Hyperf\Collection\collect;

class MongoGameQueryRepositoryTest extends InfrastructureCase
{
    use ManagedExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpResource('games', 'mongo');
    }

    public function testShouldReadGameSuccessfully(): void
    {
        $values = $this->seed(Game::class);

        $repository = $this->make(MongoGameQueryRepository::class);
        $game = $repository->getGame($values->get('id'));
        $this->assertEquals($values->get('name'), $game->name);
    }

    final public function testShouldReturnNullWhenGameNotExists(): void
    {
        $id = $this->managed()
            ->id();
        $repository = $this->make(MongoGameQueryRepository::class);
        $this->assertNull($repository->getGame($id));
    }

    public function testGetGamesReturnsGameCollection(): void
    {
        $this->seed(Game::class);
        $this->seed(Game::class);

        $repository = $this->make(MongoGameQueryRepository::class);
        $games = $repository->getGames();

        $this->assertCount(2, $games);
    }

    public function testGetGamesContainsExpectedGames(): void
    {
        $this->seed(Game::class);
        $this->seed(Game::class);
        $values = $this->seed(Game::class);
        $this->seed(Game::class);
        $this->seed(Game::class);

        $repository = $this->make(MongoGameQueryRepository::class);
        $all = $repository->getGames()
            ->all();
        $count = collect($all)
            ->filter(fn ($game) => $game->id === $values->get('id'))
            ->count();
        $this->assertEquals(1, $count);
    }

    public function testGetGamesContainsExpectedSlug(): void
    {
        $slug = $this->generator()
            ->slug();
        $this->seed(Game::class);
        $this->seed(Game::class);
        $values = $this->seed(Game::class, ['slug' => $slug]);
        $this->seed(Game::class);
        $this->seed(Game::class);

        $repository = $this->make(MongoGameQueryRepository::class);
        $game = $repository
            ->getGames(['id' => $values->get('id')])
            ->current();
        $this->assertEquals($slug, $game->slug);
    }

    public function testGetGamesReturnsEmptyCollectionWhenNoGames(): void
    {
        $repository = $this->make(MongoGameQueryRepository::class);
        $games = $repository->getGames();
        $this->assertCount(0, $games);
    }
}
