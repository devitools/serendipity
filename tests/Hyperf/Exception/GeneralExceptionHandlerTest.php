<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Exception;

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpServer\Contract\RequestInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Serendipity\Domain\Exception\Parser\Additional;
use Serendipity\Domain\Exception\Parser\Thrown;
use Serendipity\Domain\Exception\ThrowableType;
use Serendipity\Hyperf\Exception\GeneralExceptionHandler;
use Serendipity\Infrastructure\Http\ExceptionResponseNormalizer;
use Serendipity\Infrastructure\Http\JsonFormatter;
use Serendipity\Infrastructure\Http\RequestAdditionalFactory;
use Serendipity\Infrastructure\Http\ResponseType;

final class GeneralExceptionHandlerTest extends TestCase
{
    private LoggerInterface $logger;

    private JsonFormatter $formatter;

    private RequestInterface $request;

    private RequestAdditionalFactory $factory;

    private ExceptionResponseNormalizer $normalizer;

    private ConfigInterface $config;

    private GeneralExceptionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->formatter = new JsonFormatter();
        $this->factory = $this->createMock(RequestAdditionalFactory::class);
        $this->normalizer = $this->createMock(ExceptionResponseNormalizer::class);
        $this->config = $this->createMock(ConfigInterface::class);

        $this->config->method('get')
            ->with('exception.ignore', [])
            ->willReturn([]);

        $this->handler = new GeneralExceptionHandler(
            $this->logger,
            $this->request,
            $this->config,
            $this->formatter,
            $this->factory,
            $this->normalizer,
        );
    }

    public function testHandleShouldLogAlertAndReturnFormattedResponseWithUntreatedType(): void
    {
        // Arrange
        $throwable = new Exception('Test Exception');
        $response = new Response();
        $thrown = Thrown::createFrom($throwable, ThrowableType::UNTREATED);

        $additional = new Additional(
            line: 'GET /test',
            body: [],
            headers: [],
            query: [],
            message: $thrown->resume(),
            thrown: $thrown,
            errors: []
        );

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->request, $throwable)
            ->willReturn($additional);

        $this->logger->expects($this->once())
            ->method('alert')
            ->with(
                $this->stringContains('<general>'),
                $this->arrayHasKey('message')
            );

        $this->normalizer->expects($this->once())
            ->method('normalizeStatusCode')
            ->with($throwable)
            ->willReturn(500);

        $this->normalizer->expects($this->once())
            ->method('detectType')
            ->with(ThrowableType::UNTREATED)
            ->willReturn(ResponseType::ERROR);

        $this->normalizer->expects($this->once())
            ->method('normalizeBody')
            ->with(ResponseType::ERROR, $thrown->resume())
            ->willReturn($thrown->resume());

        // Act
        $result = $this->handler->handle($throwable, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertJson((string) $result->getBody());
        $this->assertStringContainsString('"status":"error"', (string) $result->getBody());
    }

    public function testHandleShouldLogDebugAndReturnFormattedResponseWithInvalidInputType(): void
    {
        // Arrange
        $throwable = new Exception('Test Exception', 400);
        $response = new Response();
        $thrown = Thrown::createFrom($throwable, ThrowableType::INVALID_INPUT);

        $additional = new Additional(
            line: 'POST /test',
            body: ['field' => 'value'],
            headers: [],
            query: [],
            message: $thrown->resume(),
            thrown: $thrown,
            errors: ['field' => 'Invalid value']
        );

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->request, $throwable)
            ->willReturn($additional);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('<general>'),
                $this->arrayHasKey('message')
            );

        $this->normalizer->expects($this->once())
            ->method('normalizeStatusCode')
            ->with($throwable)
            ->willReturn(400);

        $this->normalizer->expects($this->once())
            ->method('detectType')
            ->with(ThrowableType::INVALID_INPUT)
            ->willReturn(ResponseType::FAIL);

        $this->normalizer->expects($this->once())
            ->method('normalizeBody')
            ->with(ResponseType::FAIL, $thrown->resume())
            ->willReturn(['message' => $thrown->resume()]);

        // Act
        $result = $this->handler->handle($throwable, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(400, $result->getStatusCode());
        $this->assertJson((string) $result->getBody());
        $this->assertStringContainsString('"status":"fail"', (string) $result->getBody());
    }

    public function testHandleShouldLogInfoAndReturnFormattedResponseWithFallbackRequiredType(): void
    {
        // Arrange
        $throwable = new Exception('Test Exception', 503);
        $response = new Response();
        $thrown = Thrown::createFrom($throwable, ThrowableType::FALLBACK_REQUIRED);

        $additional = new Additional(
            line: 'GET /test',
            body: [],
            headers: [],
            query: [],
            message: $thrown->resume(),
            thrown: $thrown,
            errors: []
        );

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->request, $throwable)
            ->willReturn($additional);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('<general>'),
                $this->arrayHasKey('message')
            );

        $this->normalizer->expects($this->once())
            ->method('normalizeStatusCode')
            ->with($throwable)
            ->willReturn(503);

        $this->normalizer->expects($this->once())
            ->method('detectType')
            ->with(ThrowableType::FALLBACK_REQUIRED)
            ->willReturn(ResponseType::FAIL);

        $this->normalizer->expects($this->once())
            ->method('normalizeBody')
            ->with(ResponseType::FAIL, $thrown->resume())
            ->willReturn(['message' => $thrown->resume()]);

        // Act
        $result = $this->handler->handle($throwable, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(503, $result->getStatusCode());
        $this->assertJson((string) $result->getBody());
        $this->assertStringContainsString('"status":"fail"', (string) $result->getBody());
    }

    public function testHandleShouldLogWarningAndReturnFormattedResponseWithRetryAvailableType(): void
    {
        // Arrange
        $throwable = new Exception('Test Exception', 429);
        $response = new Response();
        $thrown = Thrown::createFrom($throwable, ThrowableType::RETRY_AVAILABLE);

        $additional = new Additional(
            line: 'GET /test',
            body: [],
            headers: [],
            query: [],
            message: $thrown->resume(),
            thrown: $thrown,
            errors: []
        );

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->request, $throwable)
            ->willReturn($additional);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('<general>'),
                $this->arrayHasKey('message')
            );

        $this->normalizer->expects($this->once())
            ->method('normalizeStatusCode')
            ->with($throwable)
            ->willReturn(429);

        $this->normalizer->expects($this->once())
            ->method('detectType')
            ->with(ThrowableType::RETRY_AVAILABLE)
            ->willReturn(ResponseType::FAIL);

        $this->normalizer->expects($this->once())
            ->method('normalizeBody')
            ->with(ResponseType::FAIL, $thrown->resume())
            ->willReturn(['message' => $thrown->resume()]);

        // Act
        $result = $this->handler->handle($throwable, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(429, $result->getStatusCode());
        $this->assertJson((string) $result->getBody());
        $this->assertStringContainsString('"status":"fail"', (string) $result->getBody());
    }

    public function testHandleShouldLogErrorAndReturnFormattedResponseWithUnrecoverableType(): void
    {
        // Arrange
        $throwable = new Exception('Test Exception', 500);
        $response = new Response();
        $thrown = Thrown::createFrom($throwable, ThrowableType::UNRECOVERABLE);

        $additional = new Additional(
            line: 'GET /test',
            body: [],
            headers: [],
            query: [],
            message: $thrown->resume(),
            thrown: $thrown,
            errors: []
        );

        $this->factory->expects($this->once())
            ->method('make')
            ->with($this->request, $throwable)
            ->willReturn($additional);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('<general>'),
                $this->arrayHasKey('message')
            );

        $this->normalizer->expects($this->once())
            ->method('normalizeStatusCode')
            ->with($throwable)
            ->willReturn(500);

        $this->normalizer->expects($this->once())
            ->method('detectType')
            ->with(ThrowableType::UNRECOVERABLE)
            ->willReturn(ResponseType::ERROR);

        $this->normalizer->expects($this->once())
            ->method('normalizeBody')
            ->with(ResponseType::ERROR, $thrown->resume())
            ->willReturn($thrown->resume());

        // Act
        $result = $this->handler->handle($throwable, $response);

        // Assert
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertJson((string) $result->getBody());
        $this->assertStringContainsString('"status":"error"', (string) $result->getBody());
    }

    public function testIsValidShouldReturnTrueWhenExceptionIsNotIgnored(): void
    {
        // Arrange
        $throwable = new Exception('Test Exception');

        // Act & Assert
        $this->assertTrue($this->handler->isValid($throwable));
    }

    public function testIsValidShouldReturnFalseWhenExceptionIsIgnored(): void
    {
        // Arrange
        $throwable = new Exception('Test Exception');

        $configWithIgnore = $this->createMock(ConfigInterface::class);
        $configWithIgnore->method('get')
            ->with('exception.ignore', [])
            ->willReturn([Exception::class]);

        $handler = new GeneralExceptionHandler(
            $this->logger,
            $this->request,
            $configWithIgnore,
            $this->formatter,
            $this->factory,
            $this->normalizer,
        );

        // Act & Assert
        $this->assertFalse($handler->isValid($throwable));
    }
}
