<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository;

use Constructo\Contract\Message;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Guzzle\ClientFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Serendipity\Domain\Contract\Support\ThrownFactory;
use Serendipity\Domain\Event\RequestExecutedEvent;
use Serendipity\Domain\Exception\RepositoryException;
use Serendipity\Hyperf\Support\HyperfThrownFactory;
use Serendipity\Infrastructure\Http\Received;

use function Constructo\Json\encode;
use function Hyperf\Support\make;

abstract class HttpRepository
{
    private readonly Client $client;

    private readonly array $options;

    private readonly EventDispatcherInterface $dispatcher;

    private readonly ThrownFactory $thrownFactory;

    public function __construct(
        ClientFactory $clientFactory,
        ?EventDispatcherInterface $dispatcher = null,
        ?ThrownFactory $thrownFactory = null,
    ) {
        $this->options = $this->options();
        $this->client = $clientFactory->create($this->options);

        $this->dispatcher = $dispatcher ?? make(EventDispatcherInterface::class);
        $this->thrownFactory = $thrownFactory ?? make(HyperfThrownFactory::class);
    }

    abstract protected function options(): array;

    /**
     * @throws RepositoryException
     */
    protected function request(string $method = 'POST', string $uri = '', array $options = []): Message
    {
        $message = null;
        /*
         * @see https://docs.guzzlephp.org/en/latest/quickstart.html#exceptions
         */
        try {
            $response = $this->client->request($method, $uri, $options);
            $message = $this->format($response);
        } catch (GuzzleException $exception) {
            $message = $this->extract($exception);
            throw new RepositoryException(static::class, $exception);
        } finally {
            $this->dispatch($options, $method, $uri, $message);
        }
        return $message;
    }

    private function extractHeaders(?ResponseInterface $response): array
    {
        return array_map(
            fn (array $item) => count($item) === 1
                ? $item[0]
                : $item,
            $response?->getHeaders() ?? []
        );
    }

    private function extractBody(?ResponseInterface $response): ?string
    {
        $body = $response?->getBody();
        return $body?->getContents();
    }

    private function format(ResponseInterface $response): Message
    {
        $headers = $this->extractHeaders($response);
        $content = $this->extractBody($response);
        return new Received($headers, $content);
    }

    private function extract(GuzzleException $exception): Message
    {
        $headers = [];
        $content = null;
        if ($exception instanceof ClientException) {
            $response = $exception->getResponse();
            $headers = $this->extractHeaders($response);
            $content = $this->extractBody($response);
        }
        $thrown = $this->thrownFactory->make($exception);
        $headers['X-Exception'] = encode($thrown->context());
        return new Received($headers, $content);
    }

    private function dispatch(array $options, string $method, string $uri, ?Message $message): void
    {
        $options = array_merge($this->options, $options);
        $event = new RequestExecutedEvent($method, $uri, $options, $message);
        $this->dispatcher->dispatch($event);
    }
}
