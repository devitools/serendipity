<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Presentation\Action;

use Constructo\Contract\Message;
use Serendipity\Example\Game\Domain\Repository\GameQueryRepository;
use Serendipity\Example\Game\Presentation\Input\SearchGamesInput;
use Serendipity\Presentation\Output\Ok;

readonly class SearchGamesAction
{
    public function __construct(private GameQueryRepository $gameQueryRepository)
    {
    }

    public function __invoke(SearchGamesInput $input): Message
    {
        $name = $input->value('name');
        $slug = $input->value('slug');
        $games = $this->gameQueryRepository->getGames([
            'names' => $name,
            'slug' => $slug,
        ]);
        return Ok::createFrom($games);
    }
}
