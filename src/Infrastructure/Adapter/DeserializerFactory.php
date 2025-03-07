<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Adapter;

use Serendipity\Domain\Contract\Adapter\DeserializerFactory as Contract;
use Serendipity\Domain\Contract\Formatter;

class DeserializerFactory implements Contract
{
    /**
     * @template T of object
     * @param class-string<T> $type
     * @return Deserializer<T>
     */
    public function make(string $type): Deserializer
    {
        return new Deserializer(type: $type, formatters: $this->formatters());
    }

    /**
     * @return array<callable|Formatter>
     */
    protected function formatters(): array
    {
        return [];
    }
}
