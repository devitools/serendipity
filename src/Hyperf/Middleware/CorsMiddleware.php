<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Middleware;

use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Serendipity\Domain\Exception\Misconfiguration;

use function assert;
use function Constructo\Cast\stringify;

readonly class CorsMiddleware
{
    public function __construct(private ConfigInterface $config)
    {
    }

    /**
     * @SuppressWarnings(StaticAccess)
     */
    final public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = Context::get(ResponseInterface::class);
        assert(
            $response instanceof ResponseInterface,
            new Misconfiguration('ResponseInterface not found in context')
        );

        $origin = stringify($this->config->get('cors.allow_origin', '*'));
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader(
                'Access-Control-Allow-Headers',
                'DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization'
            )
            ->withHeader('Access-Control-Allow-Methods', '*');

        Context::set(ResponseInterface::class, $response);

        if ($request->getMethod() === 'OPTIONS') {
            return $response;
        }

        return $handler->handle($request);
    }
}
