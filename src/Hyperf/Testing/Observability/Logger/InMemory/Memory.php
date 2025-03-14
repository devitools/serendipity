<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Testing\Observability\Logger\InMemory;

use Hyperf\Collection\Collection;

final class Memory
{
    private static ?Collection $collection = null;

    public static function write(string $level, string $message, array $context = []): void
    {
        self::collection()->push(new Record($level, $message, $context));
    }

    public static function clear(): void
    {
        self::$collection = new Collection();
    }

    public static function tally(callable $where): int
    {
        return self::collection()
            ->where($where)
            ->count();
    }

    public static function all(): array
    {
        return self::collection()->all();
    }

    private static function collection(): Collection
    {
        self::$collection ??= new Collection();
        return self::$collection;
    }
}
