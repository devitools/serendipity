<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository\Formatter;

use Constructo\Type\Timestamp;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Repository\Formatter\MongoTimestampToDatabase;

final class MongoTimestampToDatabaseTest extends TestCase
{
    public function testFormatWithTimestamp(): void
    {
        // Arrange
        $converter = new MongoTimestampToDatabase();
        $timestamp = new Timestamp('2023-01-01T00:00:00+00:00');

        // Act
        $result = $converter->format($timestamp);

        // Assert
        $this->assertInstanceOf(UTCDateTime::class, $result);
    }

    public function testFormatWithString(): void
    {
        // Arrange
        $converter = new MongoTimestampToDatabase();
        $value = '2023-01-01T00:00:00+00:00';

        // Act
        $result = $converter->format($value);

        // Assert
        $this->assertInstanceOf(UTCDateTime::class, $result);
    }

    public function testFormatWithInvalidType(): void
    {
        // Arrange
        $converter = new MongoTimestampToDatabase();
        $invalidValue = 123;

        // Act
        $result = $converter->format($invalidValue);

        // Assert
        $this->assertNull($result);
    }
}
