<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Adapter\Serializing;

use InvalidArgumentException;
use Serendipity\Domain\Contract\Result;
use Serendipity\Domain\Support\Values;
use Serendipity\Infrastructure\Adapter\Serializing\Deserializer;
use Serendipity\Test\Infrastructure\Stub;
use Serendipity\Test\TestCase;

final class DeserializerTest extends TestCase
{
    public function testShouldNotDeserializeInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $mapped = new class {
        };
        $deserializer = new Deserializer(Stub::class);
        $deserializer->deserialize($mapped);
    }

    public function testShouldSerializeWhenIsNotAnInstanceOfResult(): void
    {
        $mapped = new Stub('John Doe', 30);
        $deserializer = new Deserializer(Stub::class);
        $expected = [
            'foo' => 'John Doe',
            'bar' => 30,
            'baz' => 'baz',
        ];
        $this->assertEquals($expected, $deserializer->deserialize($mapped));
    }

    public function testShouldSerializeWhenAnInstanceOfResult(): void
    {
        $mapped = new class extends Stub implements Result {
            public function __construct()
            {
                parent::__construct('John Doe', 30);
            }

            public function properties(): Values
            {
                return new Values([]);
            }

            public function content(): ?Values
            {
                return new Values(['name' => 'John Doe', 'age' => 30]);
            }
        };

        $deserializer = new Deserializer($mapped::class);
        $this->assertEquals(['name' => 'John Doe', 'age' => 30], $deserializer->deserialize($mapped));
    }
}
