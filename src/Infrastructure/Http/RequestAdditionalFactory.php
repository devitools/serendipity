<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Http;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Serendipity\Domain\Exception\InvalidInputException;
use Serendipity\Domain\Exception\Parser\Additional;
use Serendipity\Domain\Exception\Parser\ThrownFactory;
use Throwable;

use function array_map;
use function Constructo\Cast\arrayify;
use function Constructo\Cast\stringify;
use function implode;
use function is_array;

class RequestAdditionalFactory
{
    public function __construct(private readonly ThrownFactory $factory)
    {
    }

    public function make(RequestInterface|ServerRequestInterface $request, Throwable $throwable): Additional
    {
        $thrown = $this->factory->make($throwable);
        $errors = match (true) {
            $throwable instanceof ValidationException => $throwable->validator->errors()
                ->getMessages(),
            $throwable instanceof InvalidInputException => $throwable->getErrors(),
            default => [],
        };
        return new Additional(
            line: sprintf("%s %s", $request->getMethod(), $request->getUri()),
            body: $this->body($request),
            headers: $this->headers($request),
            query: $this->query($request),
            message: $thrown->resume(),
            thrown: $thrown,
            errors: $errors,
        );
    }

    private function headers(RequestInterface|ServerRequestInterface $request): array
    {
        $callback = function (mixed $header): string {
            if (! is_array($header)) {
                return stringify($header);
            }
            return implode('; ', array_map(fn (mixed $value) => stringify($value), $header));
        };
        return array_map($callback, $request->getHeaders());
    }

    private function body(RequestInterface|ServerRequestInterface $request): mixed
    {
        return match (true) {
            $request instanceof RequestInterface => $request->post(),
            $request instanceof ServerRequestInterface => $request->getBody()
                ->getContents(),
        };
    }

    private function query(RequestInterface|ServerRequestInterface $request): array
    {
        return match (true) {
            $request instanceof RequestInterface => arrayify($request->query()),
            $request instanceof ServerRequestInterface => $request->getQueryParams(),
        };
    }
}
