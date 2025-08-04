<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Adapter;

use Constructo\Contract\Formatter;
use Serendipity\Domain\Contract\Adapter\Serializer as ContractSerializer;
use Serendipity\Domain\Contract\Adapter\SerializerFactory as ContractFactory;

class SerializerFactory implements ContractFactory
{
    /**
     * @template T of object
     * @param class-string<T> $type
     * @return Serializer<T>
     */
    public function make(string $type): ContractSerializer
    {
        return new Serializer(type: $type, formatters: $this->converters());
    }

    /**
     * @return array<callable|Formatter>
     */
    protected function converters(): array
    {
        return [];
    }
}
