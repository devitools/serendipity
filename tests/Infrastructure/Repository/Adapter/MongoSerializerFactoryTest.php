<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository\Adapter;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Repository\Adapter\MongoSerializerFactory;
use Serendipity\Infrastructure\Repository\Formatter\MongoArrayToEntity;
use Serendipity\Infrastructure\Repository\Formatter\MongoDateTimeToEntity;
use Serendipity\Test\Testing\Stub\Stub;

final class MongoSerializerFactoryTest extends TestCase
{
    public function testShouldCreateSerializer(): void
    {
        $factory = new MongoSerializerFactory();
        $serializer = $factory->make(Stub::class);

        $converters = [
            DateTime::class => new MongoDateTimeToEntity(),
            DateTimeImmutable::class => new MongoDateTimeToEntity(),
            'array' => new MongoArrayToEntity(),
        ];
        $this->assertEquals($converters, $serializer->formatters);
    }
}
