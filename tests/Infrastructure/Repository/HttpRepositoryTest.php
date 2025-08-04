<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
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
        $this->assertEquals('application/json',
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
}
