<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Support;

use Constructo\Factory\DefaultTypesFactory;
use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Serendipity\Hyperf\Support\HyperfTypesFactory;

class HyperfTypesFactoryTest extends TestCase
{
    public function testConstructorWithEmptyTypes(): void
    {
        // Arrange
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('schema.types', [])
            ->willReturn([]);

        // Act
        $factory = new HyperfTypesFactory($config);

        // Assert
        $this->assertInstanceOf(HyperfTypesFactory::class, $factory);
    }

    public function testConstructorWithTypes(): void
    {
        // Arrange
        $types = [
            'string' => ['validation' => 'string'],
            'integer' => ['validation' => 'integer'],
            'boolean' => ['validation' => 'boolean'],
        ];

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('schema.types', [])
            ->willReturn($types);

        // Act
        $factory = new HyperfTypesFactory($config);

        // Assert
        $this->assertInstanceOf(HyperfTypesFactory::class, $factory);
    }

    public function testConstructorWithNonArrayTypes(): void
    {
        // Arrange
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('schema.types', [])
            ->willReturn('not-an-array');

        // Act
        $factory = new HyperfTypesFactory($config);

        // Assert
        $this->assertInstanceOf(HyperfTypesFactory::class, $factory);
    }

    public function testExtendsDefaultTypesFactory(): void
    {
        // Arrange
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')
            ->willReturn([]);

        // Act
        $factory = new HyperfTypesFactory($config);

        // Assert
        $this->assertInstanceOf(DefaultTypesFactory::class, $factory);
    }

    public function testIsReadonly(): void
    {
        // Arrange
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')
            ->willReturn([]);

        // Act
        $factory = new HyperfTypesFactory($config);

        // Assert
        $reflection = new ReflectionClass($factory);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function testUsesArrayifyFunction(): void
    {
        // Arrange
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('schema.types', [])
            ->willReturn('string-value');

        // Act & Assert - Should not throw exception due to arrayify function
        $factory = new HyperfTypesFactory($config);
        $this->assertInstanceOf(HyperfTypesFactory::class, $factory);
    }
}
