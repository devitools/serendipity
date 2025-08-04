<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Database\Document\Condition;

use DateTimeInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Database\Document\Mongo\Condition\BetweenCondition;

class BetweenConditionTest extends TestCase
{
    public function testShouldCompose(): void
    {
        $condition = new BetweenCondition();
        $composed = $condition->compose('2024-11-17 11:00:00,2024-11-17 11:35:00');
        $this->assertEquals(
            '2024-11-17T11:00:00.000+00:00',
            $composed['$gte']->toDateTime()
                ->format(DateTimeInterface::RFC3339_EXTENDED)
        );
        $this->assertEquals(
            '2024-11-17T11:35:00.000+00:00',
            $composed['$lte']->toDateTime()
                ->format(DateTimeInterface::RFC3339_EXTENDED)
        );
    }

    public function testShouldComposeWithJustDate(): void
    {
        $condition = new BetweenCondition();
        $composed = $condition->compose('2024-11-16,2024-11-17');
        $this->assertEquals(
            '2024-11-16T00:00:00.000+00:00',
            $composed['$gte']->toDateTime()
                ->format(DateTimeInterface::RFC3339_EXTENDED)
        );
        $this->assertEquals(
            '2024-11-17T23:59:59.000+00:00',
            $composed['$lte']->toDateTime()
                ->format(DateTimeInterface::RFC3339_EXTENDED)
        );
    }

    public function testShouldFailComposeWithFewArguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $condition = new BetweenCondition();
        $condition->compose('2024-11-17 11:00:00');
    }

    public function testShouldFailComposeWithInvalidDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $condition = new BetweenCondition();
        $condition->compose('abc,xyz');
    }
}
