<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Exception;

use Exception;
use Serendipity\Infrastructure\Exception\Thrown;
use Serendipity\Test\TestCase;

class ThrownTest extends TestCase
{
    public function testShouldCreateFrom(): void
    {
        // Arrange
        $throwable = new Exception(
            message: '1',
            previous: new Exception(
                message: '2',
                previous: new Exception('3')
            )
        );

        // Act
        $thrown = Thrown::createFrom($throwable);

        // Assert
        $context = $thrown->context();
        $this->assertEquals('1', $context['message']);
        $this->assertEquals('2', $context['previous']['message']);
        $this->assertEquals('3', $context['previous']['previous']['message']);
    }

    public function testShouldResume(): void
    {
        $exception = new Exception($this->generator()->sentence(3));
        $thrown = Thrown::createFrom($exception);
        $this->assertStringContainsString($exception->getMessage(), $thrown->resume());
    }
}
