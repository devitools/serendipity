<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Testing\Example\Game\Presentation;

use Serendipity\Presentation\Output\Ok;
use Serendipity\Test\Hyperf\Testing\Example\Game\PresentationTestCase;
use Serendipity\Testing\Example\Game\Domain\Entity\Game;
use Serendipity\Testing\Example\Game\Presentation\Action\SearchGamesAction;
use Serendipity\Testing\Example\Game\Presentation\Input\SearchGamesInput;

final class SearchGamesActionTest extends PresentationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpResource('games', 'sleek');
    }

    public function testShouldReturnGames(): void
    {
        $slug = $this->generator()->slug();
        $this->seed(Game::class, ['slug' => $slug]);
        $this->seed(Game::class, ['slug' => $slug]);
        $this->seed(Game::class, ['slug' => $slug]);

        $input = $this->input(class: SearchGamesInput::class, queryParams: ['slug' => $slug]);

        $action = $this->make(SearchGamesAction::class);
        $actual = $action($input);

        $this->assertInstanceOf(Ok::class, $actual);
        $this->assertCount(3, $actual->content());
    }

    public function testShouldReturnEmptyArray(): void
    {
        $input = $this->input(class: SearchGamesInput::class, params: ['id' => $this->generator()->uuid()]);

        $action = $this->make(SearchGamesAction::class);
        $actual = $action($input);

        $this->assertInstanceOf(Ok::class, $actual);
        $this->assertCount(0, $actual->content());
    }
}
