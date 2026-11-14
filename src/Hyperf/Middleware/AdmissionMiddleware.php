<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Middleware;

use GuzzleHttp\Psr7\Response;
use Hyperf\Contract\ConfigInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Serendipity\Hyperf\Admission\SharedBudget;

use function Constructo\Cast\arrayify;
use function Constructo\Cast\boolify;
use function Constructo\Cast\integerify;
use function in_array;

class AdmissionMiddleware implements MiddlewareInterface
{
    private int $active = 0;

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly SharedBudget $budget,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->bypasses($request)) {
            return $handler->handle($request);
        }
        if ($this->active >= $this->limit()) {
            return $this->refuse('concurrency');
        }

        ++$this->active;
        try {
            return $this->admit($request, $handler);
        } finally {
            --$this->active;
        }
    }

    private function bypasses(ServerRequestInterface $request): bool
    {
        return ! boolify($this->config->get('admission.enabled', false)) || in_array(
            $request->getUri()->getPath(),
            arrayify($this->config->get('admission.excluded_paths', ['/health', '/metrics'])),
            true,
        );
    }

    private function limit(): int
    {
        $limit = integerify($this->config->get('admission.concurrency', 4));
        if ($limit < 1) {
            throw new InvalidArgumentException('Admission concurrency must be positive.');
        }
        return $limit;
    }

    private function admit(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->budget->consume()) {
            return $this->refuse('rate');
        }
        $degraded = $this->budget->isDegraded();
        $response = $handler->handle($request);
        return $degraded ? $response->withHeader('Admission-Degraded', 'true') : $response;
    }

    private function refuse(string $reason): ResponseInterface
    {
        $this->logger->notice('Request refused by admission', [
            'component' => 'admission',
            'http_status' => 429,
            'reason' => $reason,
            'processed' => false,
        ]);
        return new Response(429, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
            'Retry-After' => '1',
        ], '{"code":"capacity_exceeded","processed":false}');
    }
}
