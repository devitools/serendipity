<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Adapter;

use Constructo\Contract\Message;
use Constructo\Support\Set;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Adapter\Deserializer;
use Serendipity\Test\Testing\Stub\Stub;

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
        $mapped = new class extends Stub implements Message {
            public function __construct(
                public string $name = 'John Doe',
                public int $age = 30,
            ) {
                parent::__construct($name, $age);
            }

            public function properties(): Set
            {
                return new Set([]);
            }

            public function content(): array
            {
                return [
                    'name' => $this->name,
                    'age' => $this->age,
                ];
            }
        };

        $deserializer = new Deserializer($mapped::class);
        $this->assertEquals(
            [
                'name' => 'John Doe',
                'age' => 30,
            ],
            $deserializer->deserialize($mapped)
        );
    }
}
