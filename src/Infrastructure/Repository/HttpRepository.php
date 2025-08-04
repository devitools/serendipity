<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository;

use Constructo\Contract\Message;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\ClientFactory;
use Psr\Http\Message\ResponseInterface;
use Serendipity\Domain\Exception\RepositoryException;
use Serendipity\Infrastructure\Http\Received;

abstract class HttpRepository
{
    private readonly Client $client;

    public function __construct(ClientFactory $clientFactory)
    {
        $this->client = $clientFactory->create($this->options());
    }

    abstract protected function options(): array;

    /**
     * @throws RepositoryException
     */
    protected function request(string $method = 'POST', string $uri = '', array $options = []): Message
    {
        /*
         * @see https://docs.guzzlephp.org/en/latest/quickstart.html#exceptions
         */
        try {
            $response = $this->client->request($method, $uri, $options);
            return $this->format($response);
        } catch (GuzzleException $exception) {
            throw new RepositoryException(static::class, $exception);
        }
    }

    private function format(ResponseInterface $response): Message
    {
        $headers = array_map(
            fn (array $item) => count($item) === 1
                ? $item[0]
                : $item,
            $response->getHeaders()
        );
        $content = $response->getBody()
            ->getContents();
        return new Received($headers, $content);
    }
}
