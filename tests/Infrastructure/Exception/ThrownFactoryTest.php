<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Exception\Parser\DefaultThrownFactory;
use Serendipity\Domain\Exception\ThrowableType;

final class ThrownFactoryTest extends TestCase
{
    public function testShouldMakeThrown(): void
    {
        // Arrange
        $factory = new DefaultThrownFactory([]);
        $throwable = new Exception('_|_');

        // Act
        $thrown = $factory->make($throwable);

        // Assert
        $this->assertEquals($throwable->getMessage(), $thrown->message);
    }

    public function testShouldMakeThrownWithTypeFromConfig(): void
    {
        // Arrange
        $throwable = new Exception();
        $classification = [$throwable::class => ThrowableType::INVALID_INPUT];
        $factory = new DefaultThrownFactory($classification);

        // Act
        $thrown = $factory->make($throwable);

        // Assert
        $this->assertEquals(ThrowableType::INVALID_INPUT, $thrown->type);
    }

    public function testShouldMakeThrownWithPrevious(): void
    {
        // Arrange
        $factory = new DefaultThrownFactory([]);
        $throwable = new Exception(
            message: '1',
            previous: new Exception(
                message: '2',
                previous: new Exception('3')
            )
        );

        // Act
        $thrown = $factory->make($throwable);

        // Assert
        $context = $thrown->context();
        $this->assertEquals('1', $context['message']);
        $this->assertEquals('2', $context['previous']['message']);
        $this->assertEquals('3', $context['previous']['previous']['message']);
    }
}
