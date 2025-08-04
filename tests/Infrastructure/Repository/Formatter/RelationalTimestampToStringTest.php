<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository\Formatter;

use Constructo\Type\Timestamp;
use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Repository\Formatter\RelationalTimestampToString;

final class RelationalTimestampToStringTest extends TestCase
{
    public function testFormatWithString(): void
    {
        // Arrange
        $converter = new RelationalTimestampToString();
        $value = '2023-01-01T00:00:00+00:00';

        // Act
        $result = $converter->format($value);

        // Assert
        $this->assertSame($value, $result);
    }

    public function testFormatWithTimestamp(): void
    {
        // Arrange
        $converter = new RelationalTimestampToString();
        $timestamp = new Timestamp('2023-01-01T00:00:00+00:00');
        $expected = '2023-01-01T00:00:00+00:00';

        // Act
        $result = $converter->format($timestamp);

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testFormatWithInvalidType(): void
    {
        // Arrange
        $converter = new RelationalTimestampToString();
        $invalidValue = 123;

        // Act
        $result = $converter->format($invalidValue);

        // Assert
        $this->assertNull($result);
    }
}
