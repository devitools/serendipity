<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Middleware;

use Constructo\Contract\Exportable;
use Constructo\Contract\Message;
use Constructo\Support\Set;
use Constructo\Testing\BuilderExtension;
use Constructo\Testing\FakerExtension;
use FastRoute\Dispatcher;
use Hyperf\Context\ResponseContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\Handler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Serendipity\Example\Game\Domain\Collection\GameCollection;
use Serendipity\Example\Game\Domain\Entity\Game;
use Serendipity\Hyperf\Event\HttpHandleCompleted;
use Serendipity\Hyperf\Event\HttpHandleInterrupted;
use Serendipity\Hyperf\Event\HttpHandleStarted;
use Serendipity\Hyperf\Middleware\HttpHandlerMiddleware;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Infrastructure\Adapter\Deserialize\Demolisher;
use Serendipity\Presentation\Output;
use Swow\Psr7\Message\ResponsePlusInterface;
use Swow\Psr7\Message\ServerRequestPlusInterface;

final class HttpHandlerMiddlewareTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;
    use BuilderExtension;

    public function testShouldRenderOutputResponse(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertInstanceOf(HttpHandleStarted::class, $event);
                } elseif ($callCount === 2) {
                    $this->assertInstanceOf(HttpHandleCompleted::class, $event);
                }

                return $event;
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => match ($class) {
                EventDispatcherInterface::class => $eventDispatcher,
                default => $this->createMock($class),
            });
        $middleware = new HttpHandlerMiddleware($container);

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
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertInstanceOf(HttpHandleStarted::class, $event);
                } elseif ($callCount === 2) {
                    $this->assertInstanceOf(HttpHandleCompleted::class, $event);
                }

                return $event;
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => match ($class) {
                EventDispatcherInterface::class => $eventDispatcher,
                default => $this->createMock($class),
            });
        $middleware = new HttpHandlerMiddleware($container);

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

    #[TestWith([null])]
    #[TestWith([204])]
    #[TestWith([300])]
    #[TestWith([400])]
    #[TestWith([500])]
    public function testShouldRenderByStatus(?int $statusCode): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertInstanceOf(HttpHandleStarted::class, $event);
                } elseif ($callCount === 2) {
                    $this->assertInstanceOf(HttpHandleCompleted::class, $event);
                }

                return $event;
            });

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->willReturn($statusCode);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => match ($class) {
                ConfigInterface::class => $config,
                EventDispatcherInterface::class => $eventDispatcher,
                default => $this->createMock($class),
            });
        $middleware = new HttpHandlerMiddleware($container);

        $request = $this->createMock(ServerRequestPlusInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponsePlusInterface::class);

        ResponseContext::set($response);

        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('properties')
            ->willReturn(
                Set::createFrom([
                    'Invalid-Property' => 1,
                    'Custom-Property' => 'CustomValue',
                ])
            );
        $request->method('getAttribute')
            ->willReturn(
                new Dispatched([
                    Dispatcher::FOUND,
                    new Handler(fn () => $message, ''),
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
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertInstanceOf(HttpHandleStarted::class, $event);
                } elseif ($callCount === 2) {
                    $this->assertInstanceOf(HttpHandleCompleted::class, $event);
                }

                return $event;
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => match ($class) {
                EventDispatcherInterface::class => $eventDispatcher,
                default => $this->createMock($class),
            });
        $middleware = new HttpHandlerMiddleware($container);

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

    #[DataProvider('providerShouldRenderDomain')]
    public function testShouldRenderDomain(string $context): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertInstanceOf(HttpHandleStarted::class, $event);
                } elseif ($callCount === 2) {
                    $this->assertInstanceOf(HttpHandleCompleted::class, $event);
                }

                return $event;
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => match ($class) {
                EventDispatcherInterface::class => $eventDispatcher,
                default => $this->make($class),
            });
        $middleware = new HttpHandlerMiddleware($container);

        $request = $this->createMock(ServerRequestPlusInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        ResponseContext::set(new Response());

        $set = $this->faker()
            ->fake(Game::class);
        $game = $this->builder()
            ->build(Game::class, $set);
        $collection = new GameCollection();
        $collection->push($game);

        $callback = fn () => match ($context) {
            Game::class => $game,
            GameCollection::class => $collection,
            default => null,
        };

        $request->method('getAttribute')
            ->willReturn(
                new Dispatched([
                    Dispatcher::FOUND,
                    new Handler($callback, ''),
                    [],
                ])
            );

        $demolisher = $this->make(Demolisher::class);

        $response = $middleware->process($request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
        $expected = json_encode([
            'status' => 'success',
            'data' => match ($context) {
                Game::class => $demolisher->demolish($game),
                GameCollection::class => $demolisher->demolishCollection($collection),
                default => null,
            },
        ]);
        $actual = $response->getBody()
            ->getContents();
        $this->assertEquals($expected, $actual);
    }

    public static function providerShouldRenderDomain(): array
    {
        return [
            'Game' => [
                Game::class,
            ],
            'GameCollection' => [
                GameCollection::class,
            ],
        ];
    }

    public function testShouldDispatchInterruptedEventOnException(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($event) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertInstanceOf(HttpHandleStarted::class, $event);
                } elseif ($callCount === 2) {
                    $this->assertInstanceOf(HttpHandleInterrupted::class, $event);
                    $this->assertInstanceOf(RuntimeException::class, $event->exception);
                }

                return $event;
            });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(fn (string $class) => match ($class) {
                EventDispatcherInterface::class => $eventDispatcher,
                default => $this->createMock($class),
            });
        $middleware = new HttpHandlerMiddleware($container);

        $request = $this->createMock(ServerRequestPlusInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponsePlusInterface::class);

        ResponseContext::set($response);

        $exception = new RuntimeException('Test exception');
        $request->method('getAttribute')
            ->willReturn(
                new Dispatched([
                    Dispatcher::FOUND,
                    new Handler(function () use ($exception) {
                        throw $exception;
                    }, ''),
                    [],
                ])
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        $middleware->process($request, $handler);
    }
}
