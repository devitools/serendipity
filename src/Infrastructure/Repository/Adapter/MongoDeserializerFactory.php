<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Adapter;

use Constructo\Type\Timestamp;
use DateTime;
use DateTimeImmutable;
use Serendipity\Infrastructure\Adapter\DeserializerFactory;
use Serendipity\Infrastructure\Repository\Formatter\MongoDateTimeToDatabase;
use Serendipity\Infrastructure\Repository\Formatter\MongoTimestampToDatabase;

class MongoDeserializerFactory extends DeserializerFactory
{
    protected function formatters(): array
    {
        return [
            Timestamp::class => new MongoTimestampToDatabase(),
            DateTime::class => new MongoDateTimeToDatabase(),
            DateTimeImmutable::class => new MongoDateTimeToDatabase(),
        ];
    }
}
