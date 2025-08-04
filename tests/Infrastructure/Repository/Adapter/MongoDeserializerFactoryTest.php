<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository\Adapter;

use Constructo\Type\Timestamp;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Repository\Adapter\MongoDeserializerFactory;
use Serendipity\Infrastructure\Repository\Formatter\MongoDateTimeToDatabase;
use Serendipity\Infrastructure\Repository\Formatter\MongoTimestampToDatabase;
use Serendipity\Test\Testing\Stub\Stub;

final class MongoDeserializerFactoryTest extends TestCase
{
    public function testShouldCreateDeserializer(): void
    {
        $factory = new MongoDeserializerFactory();
        $deserializer = $factory->make(Stub::class);

        $converters = [
            Timestamp::class => new MongoTimestampToDatabase(),
            DateTime::class => new MongoDateTimeToDatabase(),
            DateTimeImmutable::class => new MongoDateTimeToDatabase(),
        ];
        $this->assertEquals($converters, $deserializer->formatters);
    }
}
