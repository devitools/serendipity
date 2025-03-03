<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Infrastructure\Repository\SleekDB;

use Serendipity\Infrastructure\Repository\SleekDBRepository;

abstract class SleekDBGameRepository extends SleekDBRepository
{
    protected function resource(): string
    {
        return 'games';
    }
}
