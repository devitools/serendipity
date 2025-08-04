<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Presentation\Action;

use Constructo\Contract\Message;
use Serendipity\Domain\Exception\ManagedException;
use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Example\Game\Domain\Repository\GameCommandRepository;
use Serendipity\Example\Game\Presentation\Input\CreateGameInput;
use Serendipity\Infrastructure\Adapter\Serialize\Builder;
use Serendipity\Presentation\Output\Accepted;

readonly class CreateGameAction
{
    public function __construct(
        private Builder $builder,
        private GameCommandRepository $gameCommandRepository,
    ) {
    }

    /**
     * @throws ManagedException
     */
    public function __invoke(CreateGameInput $input): Message
    {
        $game = $this->builder->build(GameCommand::class, $input->values());
        $id = $this->gameCommandRepository->create($game);
        return Accepted::createFrom($id);
    }
}
