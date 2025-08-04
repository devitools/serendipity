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
use Serendipity\Hyperf\Database\Relational\HyperfConnectionChecker;

use function Constructo\Cast\integerify;

readonly class ConnectionCheckerMiddleware implements MiddlewareInterface
{
    private HyperfConnectionChecker $connectionChecker;

    private ConfigInterface $config;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->connectionChecker = $container->get(HyperfConnectionChecker::class);
        $this->config = $container->get(ConfigInterface::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->connectionChecker->check(
            integerify($this->config->get('databases.default.check.max_attempts', 3)),
            integerify($this->config->get('databases.default.check.delay_microseconds', 100)),
        );

        return $handler->handle($request);
    }
}
