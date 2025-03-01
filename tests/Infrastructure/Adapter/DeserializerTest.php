<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Adapter;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Contract\Message;
use Serendipity\Domain\Support\Set;
use Serendipity\Infrastructure\Adapter\Deserializer;
use Serendipity\Test\Infrastructure\Stub;

/**
 * @internal
 */
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
            public function __construct()
            {
                parent::__construct('John Doe', 30);
            }

            public function properties(): Set
            {
                return new Set([]);
            }

            public function property(string $key, mixed $default = null): mixed
            {
                return null;
            }

            public function content(): array
            {
                return ['name' => 'John Doe', 'age' => 30];
            }
        };

        $deserializer = new Deserializer($mapped::class);
        $this->assertEquals(['name' => 'John Doe', 'age' => 30], $deserializer->deserialize($mapped));
    }
}
