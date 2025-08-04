<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository\Formatter;

use Constructo\Type\Timestamp;
use DateTime;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Repository\Formatter\MongoTimestampToEntity;

final class MongoTimestampToEntityTest extends TestCase
{
    public function testFormatWithTimestamp(): void
    {
        // Arrange
        $converter = new MongoTimestampToEntity();
        $timestamp = new Timestamp('2023-01-01T00:00:00+00:00');

        // Act
        $result = $converter->format($timestamp);

        // Assert
        $this->assertSame($timestamp, $result);
    }

    public function testFormatWithUTCDateTimeAndTimestampOption(): void
    {
        // Arrange
        $converter = new MongoTimestampToEntity();
        $dateTime = new DateTime('2023-01-01T00:00:00+00:00');
        $utcDateTime = new UTCDateTime($dateTime->getTimestamp() * 1000);

        // Act
        $result = $converter->format($utcDateTime, Timestamp::class);

        // Assert
        $this->assertInstanceOf(Timestamp::class, $result);
        $this->assertEquals('2023-01-01T00:00:00+00:00', $result->toString());
    }

    public function testFormatWithUTCDateTimeAndDefaultOption(): void
    {
        // Arrange
        $converter = new MongoTimestampToEntity();
        $dateTime = new DateTime('2023-01-01T00:00:00+00:00');
        $utcDateTime = new UTCDateTime($dateTime->getTimestamp() * 1000);

        // Act
        $result = $converter->format($utcDateTime);

        // Assert
        $this->assertInstanceOf(Timestamp::class, $result);
        $this->assertEquals('2023-01-01T00:00:00+00:00', $result->toString());
    }

    public function testFormatWithString(): void
    {
        // Arrange
        $converter = new MongoTimestampToEntity();
        $value = '2023-01-01T00:00:00+00:00';

        // Act
        $result = $converter->format($value);

        // Assert
        $this->assertInstanceOf(Timestamp::class, $result);
        $this->assertEquals($value, $result->toString());
    }

    public function testFormatWithInvalidType(): void
    {
        // Arrange
        $converter = new MongoTimestampToEntity();
        $invalidValue = 123;

        // Act
        $result = $converter->format($invalidValue);

        // Assert
        $this->assertNull($result);
    }
}
