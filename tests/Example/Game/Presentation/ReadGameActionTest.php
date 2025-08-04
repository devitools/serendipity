<?php

declare(strict_types=1);

namespace Serendipity\Test\Example\Game\Presentation;

use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Presentation\Action\ReadGameAction;
use Serendipity\Example\Game\Presentation\Input\ReadGameInput;
use Serendipity\Presentation\Output\Fail\NotFound;
use Serendipity\Presentation\Output\Ok;
use Serendipity\Test\Example\Game\PresentationCase;

use function Constructo\Cast\arrayify;
use function Constructo\Cast\stringify;
use function Constructo\Util\extractString;

class ReadGameActionTest extends PresentationCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpResource('games', 'sleek');
    }

    final public function testShouldReturnOk(): void
    {
        $values = $this->seed(Game::class);

        $id = trim(stringify($values->get('id')));
        $input = $this->input(class: ReadGameInput::class, params: ['id' => $id]);

        $action = $this->make(ReadGameAction::class);
        $actual = $this->invoke($action, $input);

        $this->assertInstanceOf(Ok::class, $actual);
        $game = $actual->content();
        $this->assertInstanceOf(Game::class, $game);
        $this->assertSame($values->get('id'), $game->id);
        $this->assertSame($values->get('name'), $game->name);

        $features = arrayify($values->get('features'));
        $names = array_map(
            static fn (array $feature): string => extractString($feature, 'name'),
            $features,
        );
        foreach ($game->features as $feature) {
            $this->assertContains($feature->name, $names);
        }
    }

    final public function testShouldReturnNotFound(): void
    {
        $input = $this->input(class: ReadGameInput::class, params: [
            'id' => $this->generator()
                ->uuid(),
        ]);

        $action = $this->make(ReadGameAction::class);
        $actual = $action($input);

        $this->assertInstanceOf(NotFound::class, $actual);
    }
}
