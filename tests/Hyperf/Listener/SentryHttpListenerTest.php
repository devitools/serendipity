<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Listener;

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Contract\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Serendipity\Domain\Exception\Parser\ThrownFactory;
use Serendipity\Hyperf\Event\HttpHandleInterrupted;
use Serendipity\Hyperf\Event\HttpHandleStarted;
use Serendipity\Hyperf\Listener\SentryHttpListener;
use Serendipity\Infrastructure\Http\RequestAdditionalFactory;
use stdClass;

class SentryHttpListenerTest extends TestCase
{
    private ConfigInterface|MockObject $config;

    private LoggerInterface|MockObject $logger;

    private MockObject|RequestAdditionalFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->createMock(ConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->factory = new RequestAdditionalFactory(new ThrownFactory());
    }

    public function testListenWithoutDsn(): void
    {
        // Arrange
        $options = [];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory);

        // Act
        $result = $listener->listen();

        // Assert
        $this->assertEmpty($result);
    }

    public function testListenWithNotBooted(): void
    {
        // Arrange
        $options = [
            'dsn' => 'https://example.com/sentry',
        ];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });

        // Act
        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory);

        // Act
        $result = $listener->listen();

        // Assert
        $this->assertEquals(SentryHttpListener::EVENTS, $result);
    }

    public function testListenWithBooted(): void
    {
        // Arrange
        $options = [
            'dsn' => 'https://example.com/sentry',
            'environment' => 'testing',
        ];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, true);

        // Act
        $result = $listener->listen();

        // Assert
        $this->assertEquals(SentryHttpListener::EVENTS, $result);
    }

    public function testProcessWithBootApplication(): void
    {
        // Arrange
        $options = [
            'dsn' => 'https://example.com/sentry',
        ];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, true);
        $event = new BootApplication();

        // Act & Assert
        $listener->process($event);
    }

    public function testProcessWithHttpHandleStartedNotBooted(): void
    {
        // Arrange
        $options = [
            'dsn' => null,
        ];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });
        $this->logger->expects($this->never())
            ->method('debug');

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, false);
        $request = $this->createMock(RequestInterface::class);
        $event = new HttpHandleStarted($request);

        // Act & Assert
        $listener->process($event);
    }

    public function testProcessWithHttpHandleStarted(): void
    {
        // Arrange
        $options = [
            'dsn' => null,
        ];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Sentry initialized', $this->isType('array'));

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, true);
        $request = $this->createMock(RequestInterface::class);
        $event = new HttpHandleStarted($request);

        // Act & Assert
        $listener->process($event);
    }

    public function testProcessWithHttpHandleStartedFail(): void
    {
        // Arrange
        $options = [
            'dsn' => 'https://example.com/sentry',
        ];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });
        $this->logger->expects($this->once())
            ->method('emergency')
            ->with('Sentry initialization failed', $this->isType('array'));

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, true);
        $request = $this->createMock(RequestInterface::class);
        $event = new HttpHandleStarted($request);

        // Act & Assert
        $listener->process($event);
    }

    public function testProcessWithHttpHandleInterruptedNotBooted(): void
    {
        // Arrange
        $options = [];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });
        $this->logger->expects($this->never())
            ->method('debug');

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, false);
        $request = $this->createMock(RequestInterface::class);
        $event = new HttpHandleInterrupted($request, new Exception('Test exception'));

        // Act & Assert
        $listener->process($event);
    }

    public function testProcessWithHttpHandleInterrupted(): void
    {
        // Arrange
        $options = [];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Sentry captured exception', $this->isType('array'));

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, true);
        $request = $this->createMock(RequestInterface::class);
        $event = new HttpHandleInterrupted($request, new Exception('Test exception'));

        // Act & Assert
        $listener->process($event);
    }

    public function testProcessWithUnsupportedEventNotBooted(): void
    {
        // Arrange
        $options = [];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });
        $this->logger->expects($this->never())
            ->method('warning');

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, false);

        // Act & Assert
        $listener->process(new stdClass());
    }

    public function testProcessWithUnsupportedEvent(): void
    {
        // Arrange
        $options = [];

        $this->config->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'sentry.options' => $options,
                'sentry.debug' => true,
            });
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Sentry integration does not support this event', ['event' => stdClass::class]);

        $listener = new SentryHttpListener($this->config, $this->logger, $this->factory, true);

        // Act & Assert
        $listener->process(new stdClass());
    }
}
