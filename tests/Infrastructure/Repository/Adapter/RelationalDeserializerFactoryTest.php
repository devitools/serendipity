<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository\Adapter;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Repository\Adapter\RelationalDeserializerFactory;
use Serendipity\Infrastructure\Repository\Formatter\RelationalArrayToJson;
use Serendipity\Infrastructure\Repository\Formatter\RelationalDatetimeToString;
use Serendipity\Test\Testing\Stub\Stub;

/**
 * @internal
 */
final class RelationalDeserializerFactoryTest extends TestCase
{
    public function testShouldCreateDeserializer(): void
    {
        $factory = new RelationalDeserializerFactory();
        $serializer = $factory->make(Stub::class);

        $this->assertEquals(Stub::class, $serializer->type);
        $converters = [
            'array' => new RelationalArrayToJson(),
            DateTime::class => new RelationalDatetimeToString(),
            DateTimeImmutable::class => new RelationalDatetimeToString(),
        ];
        $this->assertEquals($converters, $serializer->formatters);
    }
}
