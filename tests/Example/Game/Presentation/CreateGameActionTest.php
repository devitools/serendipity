<?php

declare(strict_types=1);

namespace Serendipity\Test\Example\Game\Presentation;

use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Presentation\Action\CreateGameAction;
use Serendipity\Example\Game\Presentation\Input\CreateGameInput;
use Serendipity\Presentation\Output\Accepted;
use Serendipity\Test\Example\Game\PresentationCase;

class CreateGameActionTest extends PresentationCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpResource('games', 'sleek');
    }

    final public function testCreateGameSuccessfully(): void
    {
        $values = $this->faker()
            ->fake(GameCommand::class);
        $input = $this->input(CreateGameInput::class, $values->toArray());
        $action = $this->make(CreateGameAction::class);
        $result = $this->invoke($action, $input);
        $this->assertInstanceOf(Accepted::class, $result);
        $this->assertIsString($result->content());
    }
}
