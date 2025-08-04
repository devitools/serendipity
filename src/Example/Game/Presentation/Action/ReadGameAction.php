<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Presentation\Action;

use Constructo\Contract\Message;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Example\Game\Domain\Repository\GameQueryRepository;
use Serendipity\Example\Game\Presentation\Input\ReadGameInput;
use Serendipity\Presentation\Output\Fail\NotFound;
use Serendipity\Presentation\Output\Ok;

readonly class ReadGameAction
{
    public function __construct(private GameQueryRepository $gameQueryRepository)
    {
    }

    public function __invoke(ReadGameInput $input): Message
    {
        $id = $input->value('id', '');
        $game = $this->gameQueryRepository->getGame($id);
        return $game
            ? Ok::createFrom($game)
            : NotFound::createFrom(Game::class, $id);
    }
}
