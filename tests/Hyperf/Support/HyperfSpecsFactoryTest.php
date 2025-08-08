<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Support;

use Constructo\Core\Serialize\Builder;
use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Serendipity\Hyperf\Support\HyperfSpecsFactory;

class HyperfSpecsFactoryTest extends TestCase
{
    private ConfigInterface $config;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ConfigInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    ConfigInterface::class,
                    $this->config,
                ],
                [
                    Builder::class,
                    $this->createMock(Builder::class),
                ],
            ]);
    }

    public function testConstructorWithEmptySpecs(): void
    {
        // Arrange
        $this->config->expects($this->once())
            ->method('get')
            ->with('schema.specs', [])
            ->willReturn(null);

        // Act
        $factory = new HyperfSpecsFactory($this->container);

        // Assert
        $this->assertFalse(
            $factory->make()
                ->has('required')
        );
    }

    public function testConstructorWithSpecs(): void
    {
        // Arrange
        $specs = [
            'required' => ['message' => 'This field is required'],
            'string' => ['message' => 'This field must be a string'],
        ];
        $this->config->expects($this->once())
            ->method('get')
            ->with('schema.specs', [])
            ->willReturn($specs);

        // Act
        $factory = new HyperfSpecsFactory($this->container);

        // Assert
        $reflector = $factory->make();
        $this->assertTrue($reflector->has('required'));
    }
}
