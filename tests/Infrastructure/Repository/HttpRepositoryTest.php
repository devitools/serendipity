<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Serendipity\Domain\Contract\Support\ThrownFactory;
use Serendipity\Domain\Exception\Parser\Thrown;
use Serendipity\Domain\Exception\RepositoryException;

class HttpRepositoryTest extends TestCase
{
    public function testShouldHaveContentAndProperties(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getContents')
            ->willReturn('{"message": "Hello, World!"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Content-Type' => ['application/json']]);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', '', [])
            ->willReturn($response);

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($client);

        $repository = new HttpRepositoryTestMock($clientFactory);
        $response = $repository->exposeRequest();

        $this->assertEquals('{"message": "Hello, World!"}', $response->content());
        $this->assertEquals(
            'application/json',
            $response->properties()
                ->get('Content-Type')
        );
    }

    public function testShouldRaiseGeneralException(): void
    {
        $this->expectException(RepositoryException::class);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', '', [])
            ->willThrowException(
                new BadResponseException(
                    'Internal Server Error',
                    $this->createMock(RequestInterface::class),
                    $this->createMock(ResponseInterface::class)
                )
            );

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($client);

        $repository = new HttpRepositoryTestMock($clientFactory);
        $repository->exposeRequest();
    }

    public function testShouldExtractHeadersWithMultipleValues(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getContents')
            ->willReturn('{"data": "test"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getHeaders')
            ->willReturn([
                'Content-Type' => ['application/json'],
                'Set-Cookie' => ['cookie1=value1', 'cookie2=value2'],
            ]);
        $response->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('GET', '/test', [])
            ->willReturn($response);

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($client);

        $repository = new HttpRepositoryTestMock($clientFactory);
        $message = $repository->exposeRequest('GET', '/test');

        $this->assertEquals('{"data": "test"}', $message->content());
        $this->assertEquals('application/json', $message->properties()->get('Content-Type'));
        $this->assertEquals(['cookie1=value1', 'cookie2=value2'], $message->properties()->get('Set-Cookie'));
    }

    public function testShouldHandleClientExceptionWithResponse(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn('{"error": "Bad Request"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getHeaders')
            ->willReturn(['Content-Type' => ['application/json']]);
        $response->method('getBody')
            ->willReturn($stream);

        $exception = new ClientException(
            'Bad Request',
            $this->createMock(RequestInterface::class),
            $response
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', '', [])
            ->willThrowException($exception);

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($client);

        $thrownFactory = $this->createMock(ThrownFactory::class);
        $thrownFactory->expects($this->once())
            ->method('make')
            ->with($exception)
            ->willReturn(Thrown::createFrom($exception));

        try {
            $repository = new HttpRepositoryTestMock($clientFactory, $thrownFactory);
            $repository->exposeRequest();
            $this->fail('Expected RepositoryException to be thrown');
        } catch (RepositoryException $e) {
            $this->assertInstanceOf(RepositoryException::class, $e);
        }
    }

    public function testShouldHandleNonClientException(): void
    {
        $this->expectException(RepositoryException::class);

        $exception = new ConnectException(
            'Connection refused',
            $this->createMock(RequestInterface::class)
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('request')
            ->with('POST', '', [])
            ->willThrowException($exception);

        $clientFactory = $this->createMock(ClientFactory::class);
        $clientFactory->expects($this->once())
            ->method('create')
            ->willReturn($client);

        $thrownFactory = $this->createMock(ThrownFactory::class);
        $thrownFactory->expects($this->once())
            ->method('make')
            ->with($exception)
            ->willReturn(Thrown::createFrom($exception));

        $repository = new HttpRepositoryTestMock($clientFactory, $thrownFactory);
        $repository->exposeRequest();
    }
}
