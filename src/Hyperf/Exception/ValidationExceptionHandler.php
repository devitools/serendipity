<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Exception;

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Serendipity\Domain\Exception\InvalidInputException;
use Serendipity\Domain\Exception\Parser\AdditionalFactory;
use Serendipity\Infrastructure\Http\ExceptionResponseNormalizer;
use Serendipity\Infrastructure\Http\JsonFormatter;
use Serendipity\Infrastructure\Http\ResponseType;
use Swow\Psr7\Message\ResponsePlusInterface;
use Throwable;

use function sprintf;

class ValidationExceptionHandler extends ExceptionHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestInterface $request,
        private readonly JsonFormatter $formatter,
        private readonly AdditionalFactory $factory,
        private readonly ExceptionResponseNormalizer $normalizer,
    ) {
    }

    public function handle(Throwable $throwable, ResponsePlusInterface $response): MessageInterface|ResponseInterface
    {
        $this->stopPropagation();

        $additional = $this->factory->make($this->request, $throwable);

        $message = sprintf('<validation> %s', $additional->message);
        $context = $additional->context();
        $this->logger->debug($message, $context);

        return $response
            ->setStatus($this->normalizer->normalizeStatusCode($throwable, 400))
            ->addHeader('content-type', 'application/json; charset=utf-8')
            ->setBody(new SwooleStream($this->formatter->format($context, ResponseType::FAIL)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException || $throwable instanceof InvalidInputException;
    }
}
