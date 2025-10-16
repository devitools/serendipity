<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Middleware;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionChecker;
use Serendipity\Hyperf\Middleware\ConnectionCheckerMiddleware;

final class ConnectionCheckerMiddlewareTest extends TestCase
{
    public function testShouldCheckConnectionWithDefaultSettings(): void
    {
        // Arrange
        $container = $this->createMock(ContainerInterface::class);

        $connectionChecker = $this->createMock(HyperfConnectionChecker::class);
        $config = $this->createMock(ConfigInterface::class);

        $container->method('get')
            ->willReturnCallback(fn (string $id) => match ($id) {
                HyperfConnectionChecker::class => $connectionChecker,
                ConfigInterface::class => $config,
                default => null,
            });

        $config->method('get')
            ->willReturnCallback(fn ($key, $default) => $default);

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturn($response);

        // Assert
        $connectionChecker->expects($this->once())
            ->method('check')
            ->with(3, 100);

        // Act
        $middleware = new ConnectionCheckerMiddleware($container);
        $result = $middleware->process($request, $handler);

        // Assert
        $this->assertSame($response, $result);
    }

    public function testShouldCheckConnectionWithCustomSettings(): void
    {
        // Arrange
        $connectionChecker = $this->createMock(HyperfConnectionChecker::class);
        $config = $this->createMock(ConfigInterface::class);
        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')
            ->willReturnCallback(fn (string $id) => match ($id) {
                HyperfConnectionChecker::class => $connectionChecker,
                ConfigInterface::class => $config,
                default => null,
            });

        $config->method('get')
            ->willReturnCallback(fn ($key, $default) => match ($key) {
                'databases.default.check.max_attempts' => 3,
                'databases.default.check.delay_microseconds' => 500,
                default => $default,
            });

        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')
            ->willReturn($response);

        $connectionChecker->expects($this->once())
            ->method('check')
            ->with(3, 500);

        // Act
        $middleware = new ConnectionCheckerMiddleware($container);
        $result = $middleware->process($request, $handler);

        // Assert
        $this->assertSame($response, $result);
    }
}
