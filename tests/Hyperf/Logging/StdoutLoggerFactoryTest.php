<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Logging;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Serendipity\Hyperf\Logging\StdoutLoggerFactory;
use Serendipity\Infrastructure\Logging\StdoutLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

final class StdoutLoggerFactoryTest extends TestCase
{
    private ContainerInterface $container;

    private ConfigInterface $config;

    private ConsoleOutput $consoleOutput;

    private StdoutLoggerFactory $stdoutLoggerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->consoleOutput = $this->createMock(ConsoleOutput::class);

        $this->stdoutLoggerFactory = new StdoutLoggerFactory(
            container: $this->container
        );
    }

    public function testMakeShouldReturnStdoutLoggerWithDefaultConfig(): void
    {
        // Arrange
        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $key, mixed $default) => match ($key) {
                'logger.default.levels',
                'logger.default.format' => $default,
            });

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    ConsoleOutput::class,
                    $this->consoleOutput,
                ],
                [
                    ConfigInterface::class,
                    $this->config,
                ],
            ]);

        // Act
        $logger = $this->stdoutLoggerFactory->make('test-env');

        // Assert
        $this->assertInstanceOf(StdoutLogger::class, $logger);
    }

    public function testMakeShouldReturnStdoutLoggerWithCustomConfig(): void
    {
        // Arrange
        $customLevels = [
            'error',
            'critical',
        ];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $key, mixed $default) => match ($key) {
                'logger.default.levels' => $customLevels,
                'logger.default.format' => $default,
            });

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    ConsoleOutput::class,
                    $this->consoleOutput,
                ],
                [
                    ConfigInterface::class,
                    $this->config,
                ],
            ]);

        // Act
        $logger = $this->stdoutLoggerFactory->make('production');

        // Assert
        $this->assertInstanceOf(StdoutLogger::class, $logger);
        $this->assertEquals($customLevels, $logger->levels);
    }

    public function testMakeShouldHandleNullLevelsConfig(): void
    {
        // Arrange
        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $key, mixed $default) => match ($key) {
                'logger.default.levels' => null,
                'logger.default.format' => $default,
            });

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    ConsoleOutput::class,
                    $this->consoleOutput,
                ],
                [
                    ConfigInterface::class,
                    $this->config,
                ],
            ]);

        // Act
        $logger = $this->stdoutLoggerFactory->make('dev');

        // Assert
        $this->assertInstanceOf(StdoutLogger::class, $logger);
        $this->assertEquals([], $logger->levels);
    }
}
