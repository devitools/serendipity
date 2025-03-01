<?php

declare(strict_types=1);

namespace Serendipity\Testing\Example\Game\Domain\Collection;

use Serendipity\Domain\Collection\Collection;
use Serendipity\Testing\Example\Game\Domain\Entity\Game;

/**
 * @extends Collection<Game>
 */
final class GameCollection extends Collection
{
    public function current(): Game
    {
        return $this->validate($this->datum());
    }

    protected function validate(mixed $datum): Game
    {
        return ($datum instanceof Game) ? $datum : throw $this->exception(Game::class, $datum);
    }
}
