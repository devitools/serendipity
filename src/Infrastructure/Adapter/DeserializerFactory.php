<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Adapter;

use Constructo\Contract\Formatter;
use Serendipity\Domain\Contract\Adapter\Deserializer as ContractDeserializer;
use Serendipity\Domain\Contract\Adapter\DeserializerFactory as ContractFactory;

class DeserializerFactory implements ContractFactory
{
    /**
     * @template T of object
     * @param class-string<T> $type
     * @return Deserializer<T>
     */
    public function make(string $type): ContractDeserializer
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
