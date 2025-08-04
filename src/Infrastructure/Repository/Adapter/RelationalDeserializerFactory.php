<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Adapter;

use Constructo\Type\Timestamp;
use DateTime;
use DateTimeImmutable;
use Serendipity\Infrastructure\Adapter\DeserializerFactory;
use Serendipity\Infrastructure\Repository\Formatter\RelationalArrayToJson;
use Serendipity\Infrastructure\Repository\Formatter\RelationalDatetimeToString;
use Serendipity\Infrastructure\Repository\Formatter\RelationalTimestampToString;

class RelationalDeserializerFactory extends DeserializerFactory
{
    protected function formatters(): array
    {
        return [
            'array' => new RelationalArrayToJson(),
            Timestamp::class => new RelationalTimestampToString(),
            DateTime::class => new RelationalDatetimeToString(),
            DateTimeImmutable::class => new RelationalDatetimeToString(),
        ];
    }
}
