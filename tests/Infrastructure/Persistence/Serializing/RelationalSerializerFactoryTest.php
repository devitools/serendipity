<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Persistence\Serializing;

use Serendipity\Infrastructure\Persistence\Converter\FromDatabaseToArray;
use Serendipity\Infrastructure\Persistence\Serializing\RelationalSerializerFactory;
use Serendipity\Test\Infrastructure\Stub;
use Serendipity\Test\TestCase;

final class RelationalSerializerFactoryTest extends TestCase
{
    public function testShouldCreateSerializer(): void
    {
        $factory = new RelationalSerializerFactory();
        $serializer = $factory->make(Stub::class);

        $this->assertEquals(Stub::class, $serializer->type);
        $converters = ['array' => new FromDatabaseToArray()];
        $this->assertEquals($converters, $serializer->converters);
    }
}
