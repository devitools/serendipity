<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Presentation\Input;

use Override;
use Serendipity\Presentation\Input;

class SearchGamesInput extends Input
{
    #[Override]
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
            ],
            'slug' => [
                'sometimes',
                'string',
            ],
        ];
    }
}
