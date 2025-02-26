<?php

declare(strict_types=1);

namespace Serendipity\Presentation\Output;

use Serendipity\Infrastructure\Adapter\Output;

class Accepted extends Output
{
    public function __construct(int|string $token)
    {
        parent::__construct(content: ['token' => $token]);
    }
}
