<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Adapter;

use Constructo\Type\Timestamp;
use DateTime;
use DateTimeImmutable;
use Serendipity\Domain\Collection\Collection;
use Serendipity\Infrastructure\Adapter\SerializerFactory;
use Serendipity\Infrastructure\Repository\Formatter\MongoArrayToEntity;
use Serendipity\Infrastructure\Repository\Formatter\MongoDateTimeToEntity;
use Serendipity\Infrastructure\Repository\Formatter\MongoTimestampToEntity;

class MongoSerializerFactory extends SerializerFactory
{
    protected function converters(): array
    {
        return [
            Timestamp::class => new MongoTimestampToEntity(),
            DateTime::class => new MongoDateTimeToEntity(),
            DateTimeImmutable::class => new MongoDateTimeToEntity(),
            Collection::class => new MongoArrayToEntity(),
            'array' => new MongoArrayToEntity(),
        ];
    }
}
