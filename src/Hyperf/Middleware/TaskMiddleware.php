<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Middleware;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Serendipity\Domain\Support\Task;
use Throwable;

use function array_map;
use function Constructo\Cast\arrayify;
use function Constructo\Cast\stringify;
use function Hyperf\Collection\data_get;
use function Constructo\Notation\upperify;

readonly class TaskMiddleware implements MiddlewareInterface
{
    private Task $task;

    private ConfigInterface $config;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected ContainerInterface $container)
    {
        $this->task = $container->get(Task::class);
        $this->config = $container->get(ConfigInterface::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $operation = sprintf(
            '%s:%s',
            upperify($request->getMethod()),
            $request->getUri()
                ->getPath()
        );
        $this->task
            ->setResource($operation)
            ->setCorrelationId($this->extractCorrelationId($request))
            ->setInvokerId($this->extractPlatformId($request));

        return $handler->handle($request);
    }

    private function extractCorrelationId(ServerRequestInterface $request): string
    {
        try {
            $location = $this->location(
                $request,
                'correlation_id',
                [
                    'X-Correlation-ID',
                    'header',
                ]
            );
            return $this->extract($request, ...$location)
                ?: 'N/A';
        } catch (Throwable) {
            return 'ERR';
        }
    }

    private function extractPlatformId(ServerRequestInterface $request): string
    {
        try {
            $location = $this->location(
                $request,
                'invoker_id',
                [
                    'X-Invoker-ID',
                    'header',
                ]
            );
            return $this->extract($request, ...$location)
                ?: 'N/A';
        } catch (Throwable) {
            return 'ERR';
        }
    }

    /**
     * @return array<string>
     */
    private function location(ServerRequestInterface $request, string $key, array $default): array
    {
        $path = sprintf(
            'task.%s:%s.%s',
            upperify($request->getMethod()),
            $request->getUri()
                ->getPath(),
            $key,
        );
        $location = arrayify($this->config->get($path, []));
        if (empty($location)) {
            $path = sprintf('task.default.%s', $key);
            $location = arrayify($this->config->get($path, $default));
        }
        return array_map(fn ($item) => stringify($item), $location);
    }

    private function extract(ServerRequestInterface $request, string $key, string $type = ''): string
    {
        $extracted = match ($type) {
            'header' => $request->getHeaderLine($key),
            'query' => $request->getQueryParams()[$key] ?? '',
            'cookie' => $request->getCookieParams()[$key] ?? '',
            'body' => data_get(arrayify($request->getParsedBody()), $key, ''),
            default => '',
        };
        return stringify($extracted);
    }
}
