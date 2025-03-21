<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Middleware;

use FastRoute\Dispatcher;
use Hyperf\Context\ResponseContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\Handler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Serendipity\Domain\Contract\Exportable;
use Serendipity\Hyperf\Middleware\AppMiddleware;
use Serendipity\Presentation\Output;
use Serendipity\Presentation\Output\NoContent;
use Swow\Psr7\Message\ResponsePlusInterface;
use Swow\Psr7\Message\ServerRequestPlusInterface;

/**
 * @internal
 */
final class AppMiddlewareTest extends TestCase
{
    public function testShouldRenderOutputResponse(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => $this->createMock($class));
        $middleware = new AppMiddleware($container);

        $request = $this->createMock(ServerRequestPlusInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponsePlusInterface::class);

        ResponseContext::set($response);

        $properties = [
            'Invalid-Property' => 1,
            'Custom-Property' => 'CustomValue',
        ];
        $output = new Output(null, $properties);

        $request->method('getAttribute')
            ->willReturn(
                new Dispatched([
                    Dispatcher::FOUND,
                    new Handler(fn () => $output, ''),
                    [],
                ])
            );

        $response->expects($this->once())
            ->method('addHeader')
            ->with('content-type', 'application/json')
            ->willReturnSelf();

        $middleware->process($request, $handler);
    }

    public function testShouldRenderWithoutOutput(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => $this->createMock($class));
        $middleware = new AppMiddleware($container);

        $request = $this->createMock(ServerRequestPlusInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponsePlusInterface::class);

        ResponseContext::set($response);

        $request->expects($this->once())
            ->method('getAttribute')
            ->willReturn(
                new Dispatched([
                    Dispatcher::FOUND,
                    new Handler(fn () => null, ''),
                    [],
                ])
            );

        $middleware->process($request, $handler);
    }

    public function testShouldRenderNoContentResponse(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with(sprintf('http.result.%s.status', NoContent::class))
            ->willReturn(204);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => match ($class) {
                ConfigInterface::class => $config,
                default => $this->createMock($class),
            });
        $middleware = new AppMiddleware($container);

        $request = $this->createMock(ServerRequestPlusInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponsePlusInterface::class);

        ResponseContext::set($response);

        $request->method('getAttribute')
            ->willReturn(
                new Dispatched([
                    Dispatcher::FOUND,
                    new Handler(fn () => new NoContent(), ''),
                    [],
                ])
            );

        $response->expects($this->once())
            ->method('addHeader')
            ->with('content-type', 'application/json')
            ->willReturnSelf();

        $middleware->process($request, $handler);
    }

    public function testShouldRenderExportableResponse(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => $this->createMock($class));
        $middleware = new AppMiddleware($container);

        $request = $this->createMock(ServerRequestPlusInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponsePlusInterface::class);

        ResponseContext::set($response);

        $exportable = $this->createMock(Exportable::class);
        $exportable->method('export')
            ->willReturn(['key' => 'value']);

        $request->method('getAttribute')
            ->willReturn(
                new Dispatched([
                    Dispatcher::FOUND,
                    new Handler(fn () => $exportable, ''),
                    [],
                ])
            );

        $response->expects($this->once())
            ->method('addHeader')
            ->with('content-type', 'application/json')
            ->willReturnSelf();

        $middleware->process($request, $handler);
    }
}
