<?php

declare(strict_types=1);

namespace Serendipity\Example\Game\Infrastructure\Repository\SleekDB;

use Serendipity\Infrastructure\Repository\SleekDBRepository;

/**
 * @template T of object
 * @extends SleekDBRepository<T>
 */
abstract class SleekDBGameRepository extends SleekDBRepository
{
    protected function resource(): string
    {
        return 'games';
    }
}
