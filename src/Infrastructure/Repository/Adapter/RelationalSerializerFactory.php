<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Adapter;

use Constructo\Type\Collection;
use Serendipity\Infrastructure\Adapter\SerializerFactory;
use Serendipity\Infrastructure\Repository\Formatter\RelationalJsonToArray;

class RelationalSerializerFactory extends SerializerFactory
{
    protected function converters(): array
    {
        return [
            Collection::class => new RelationalJsonToArray(),
            'array' => new RelationalJsonToArray(),
        ];
    }
}
