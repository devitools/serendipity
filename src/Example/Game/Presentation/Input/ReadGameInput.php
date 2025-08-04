<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Presentation\Input;

use Constructo\Support\Metadata\Schema;
use Override;
use Serendipity\Presentation\ReflectorInput;

class ReadGameInput extends ReflectorInput
{
    #[Override]
    protected function using(Schema $schema): Schema
    {
        $schema->add('id')
            ->required()
            ->string();
        return $schema;
    }
}
