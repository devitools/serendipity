<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Exception;

use Hyperf\Contract\MessageBag;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Serendipity\Domain\Exception\InvalidInputException;
use Serendipity\Domain\Exception\Parser\ThrownFactory;
use Serendipity\Hyperf\Exception\ValidationExceptionHandler;
use Serendipity\Infrastructure\Http\ExceptionResponseNormalizer;
use Serendipity\Infrastructure\Http\JsonFormatter;
use Serendipity\Infrastructure\Http\RequestAdditionalFactory;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

use function Serendipity\Type\Json\decode;

final class ValidationExceptionHandlerTest extends TestCase
{
    public function testHandleShouldReturnValidationErrors(): void
    {
        // Arrange
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->willReturnCallback(function (string $message, array $context) {
                $this->assertStringContainsString('<validation>', $message);
                $this->assertArrayHasKey('headers', $context);
                $this->assertArrayHasKey('errors', $context);
            });

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('getHeaders')
            ->willReturn([
                'accept' => [
                    'application/json',
                    'UTF-8',
                ],
                'content-type' => 'text/xml',
            ]);
        $request->method('getMethod')
            ->willReturn('POST');

        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')
            ->willReturn('/api/test');
        $request->method('getUri')
            ->willReturn($uri);

        $request->method('post')
            ->willReturn(
                [
                    'name' => '',
                    'slug' => '',
                ]
            );
        $request->method('query')
            ->willReturn([]);

        // Use real implementations for these classes
        $formatter = new JsonFormatter();
        $thrownFactory = new ThrownFactory();
        $additionalFactory = new RequestAdditionalFactory($thrownFactory);
        $normalizer = new ExceptionResponseNormalizer();

        $handler = new ValidationExceptionHandler(
            $logger,
            $request,
            $formatter,
            $additionalFactory,
            $normalizer
        );

        $response = $this->createMock(ResponsePlusInterface::class);
        $response->expects($this->once())
            ->method('setStatus')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('addHeader')
            ->with('content-type', 'application/json; charset=utf-8')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('setBody')
            ->willReturnSelf();

        $messageBag = $this->createMock(MessageBag::class);
        $messageBag->method('getMessages')
            ->willReturn(decode('{"name":["validation.required"],"slug":["validation.required"]}'));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->method('errors')
            ->willReturn($messageBag);

        $throwable = new ValidationException($validator);

        // Act
        $result = $handler->handle($throwable, $response);

        // Assert
        $this->assertInstanceOf(ResponsePlusInterface::class, $result);
    }

    public function testHandleShouldReturnInvalidInputErrors(): void
    {
        // Arrange
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->willReturnCallback(function (string $message, array $context) {
                $this->assertStringContainsString('<validation>', $message);
                $this->assertArrayHasKey('errors', $context);
            });

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')
            ->willReturn('POST');

        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')
            ->willReturn('/api/test');
        $request->method('getUri')
            ->willReturn($uri);

        $request->method('post')
            ->willReturn(['field' => 'value']);
        $request->method('query')
            ->willReturn([]);
        $request->method('getHeaders')
            ->willReturn([]);

        // Use real implementations for these classes
        $formatter = new JsonFormatter();
        $thrownFactory = new ThrownFactory();
        $additionalFactory = new RequestAdditionalFactory($thrownFactory);
        $normalizer = new ExceptionResponseNormalizer();

        $handler = new ValidationExceptionHandler(
            $logger,
            $request,
            $formatter,
            $additionalFactory,
            $normalizer
        );

        $response = $this->createMock(ResponsePlusInterface::class);
        $response->expects($this->once())
            ->method('setStatus')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('addHeader')
            ->with('content-type', 'application/json; charset=utf-8')
            ->willReturnSelf();
        $response->expects($this->once())
            ->method('setBody')
            ->willReturnSelf();

        $throwable = new InvalidInputException([
            'source.[0].field:target' => "Mapping right side (formatter) must be a 'callable', got '%s'",
        ]);

        // Act
        $result = $handler->handle($throwable, $response);

        // Assert
        $this->assertInstanceOf(ResponsePlusInterface::class, $result);
    }

    public function testIsValidShouldReturnTrueForValidationException(): void
    {
        // Arrange
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->createMock(RequestInterface::class);

        // Use real implementations for these classes
        $formatter = new JsonFormatter();
        $thrownFactory = new ThrownFactory();
        $additionalFactory = new RequestAdditionalFactory($thrownFactory);
        $normalizer = new ExceptionResponseNormalizer();

        $handler = new ValidationExceptionHandler(
            $logger,
            $request,
            $formatter,
            $additionalFactory,
            $normalizer
        );

        $validator = $this->createMock(ValidatorInterface::class);
        $throwable = new ValidationException($validator);

        // Act & Assert
        $this->assertTrue($handler->isValid($throwable));
    }

    public function testIsValidShouldReturnFalseForNonValidationException(): void
    {
        // Arrange
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->createMock(RequestInterface::class);

        // Use real implementations for these classes
        $formatter = new JsonFormatter();
        $thrownFactory = new ThrownFactory();
        $additionalFactory = new RequestAdditionalFactory($thrownFactory);
        $normalizer = new ExceptionResponseNormalizer();

        $handler = new ValidationExceptionHandler(
            $logger,
            $request,
            $formatter,
            $additionalFactory,
            $normalizer
        );
        $throwable = $this->createMock(Throwable::class);

        // Act & Assert
        $this->assertFalse($handler->isValid($throwable));
    }

    public function testIsValidShouldReturnTrueForInvalidInputException(): void
    {
        // Arrange
        $logger = $this->createMock(LoggerInterface::class);
        $request = $this->createMock(RequestInterface::class);

        // Use real implementations for these classes
        $formatter = new JsonFormatter();
        $thrownFactory = new ThrownFactory();
        $additionalFactory = new RequestAdditionalFactory($thrownFactory);
        $normalizer = new ExceptionResponseNormalizer();

        $handler = new ValidationExceptionHandler(
            $logger,
            $request,
            $formatter,
            $additionalFactory,
            $normalizer
        );

        $throwable = new InvalidInputException([
            'source.[0].field:target' => "Mapping right side (formatter) must be a 'callable', got '%s'",
        ]);

        // Act & Assert
        $this->assertTrue($handler->isValid($throwable));
    }
}
