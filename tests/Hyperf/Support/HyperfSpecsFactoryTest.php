<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Support;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Support\HyperfSpecsFactory;

class HyperfSpecsFactoryTest extends TestCase
{
    public function testConstructorWithEmptySpecs(): void
    {
        // Arrange
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('schema.specs', [])
            ->willReturn([]);

        // Act
        $factory = new HyperfSpecsFactory($config);

        // Assert
        $this->assertInstanceOf(HyperfSpecsFactory::class, $factory);
    }

    public function testConstructorWithSpecs(): void
    {
        // Arrange
        $specs = [
            'required' => ['message' => 'This field is required'],
            'string' => ['message' => 'This field must be a string'],
        ];

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('schema.specs', [])
            ->willReturn($specs);

        // Act
        $factory = new HyperfSpecsFactory($config);

        // Assert
        $this->assertInstanceOf(HyperfSpecsFactory::class, $factory);
    }

    public function testExtendsDefaultSpecsFactory(): void
    {
        // Arrange
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')
            ->willReturn([]);

        // Act
        $factory = new HyperfSpecsFactory($config);

        // Assert
        $this->assertInstanceOf(\Constructo\Factory\DefaultSpecsFactory::class, $factory);
    }

    public function testIsReadonly(): void
    {
        // Arrange
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')
            ->willReturn([]);

        // Act
        $factory = new HyperfSpecsFactory($config);

        // Assert
        $reflection = new \ReflectionClass($factory);
        $this->assertTrue($reflection->isReadOnly());
    }
}
