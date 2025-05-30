<?php

declare(strict_types=1);

namespace Serendipity\Example\Health;

use Serendipity\Presentation\Input;

final class HealthInput extends Input
{
    public function rules(): array
    {
        return [
            'message' => 'sometimes|string',
        ];
    }
}
