<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Presentation\Input;

use Serendipity\Example\Game\Domain\Entity\Command\GameCommand;
use Serendipity\Presentation\ReflectorInput;

class CreateGameInput extends ReflectorInput
{
    protected ?string $source = GameCommand::class;
}
