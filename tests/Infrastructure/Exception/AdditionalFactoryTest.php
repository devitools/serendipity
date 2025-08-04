<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Exception;

use Exception;
use Hyperf\HttpServer\Contract\RequestInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Serendipity\Domain\Exception\InvalidInputException;
use Serendipity\Domain\Exception\Parser\AdditionalFactory;
use Serendipity\Domain\Exception\Parser\Thrown;
use Serendipity\Domain\Exception\Parser\ThrownFactory;
use Throwable;

final class AdditionalFactoryTest extends TestCase
{
    private AdditionalFactory $additionalFactory;

    protected function setUp(): void
    {
        $this->additionalFactory = new AdditionalFactory(new ThrownFactory());
    }

    #[TestWith([new Exception('Test exception')])]
    #[TestWith([new InvalidInputException(['error'])])]
    public function testShouldCreateAdditionalFactory(Throwable $throwable): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')
            ->willReturn('/api/test');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');
        $request->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);
        $request->expects($this->once())
            ->method('post')
            ->willReturn(['key' => 'value']);
        $request->expects($this->once())
            ->method('getHeaders')
            ->willReturn([
                'Content-Type' => ['application/json'],
                'Accept' => '*/*',
            ]);
        $request->expects($this->once())
            ->method('query')
            ->willReturn(['param' => 'value']);

        $additional = $this->additionalFactory->make($request, $throwable);

        $thrown = Thrown::createFrom($throwable);
        $errors = match (true) {
            $throwable instanceof InvalidInputException => $throwable->getErrors(),
            default => [],
        };

        $this->assertEquals('POST /api/test', $additional->line);
        $this->assertEquals(['key' => 'value'], $additional->body);
        $this->assertEquals(
            [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
            ],
            $additional->headers
        );
        $this->assertEquals(['param' => 'value'], $additional->query);
        $this->assertEquals($thrown->resume(), $additional->message);
        $this->assertEquals($errors, $additional->errors);
    }
}
