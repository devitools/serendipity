<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Exception;

use Hyperf\Contract\ConfigInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Serendipity\Domain\Exception\ThrowableType;
use Serendipity\Infrastructure\Http\ExceptionResponseNormalizer;
use Serendipity\Infrastructure\Http\JsonFormatter;
use Serendipity\Infrastructure\Http\RequestAdditionalFactory;
use Throwable;

use function array_map;
use function Constructo\Cast\arrayify;
use function Constructo\Cast\stringify;
use function in_array;
use function sprintf;

class GeneralExceptionHandler extends ExceptionHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestInterface $request,
        private readonly ConfigInterface $config,
        private readonly JsonFormatter $formatter,
        private readonly RequestAdditionalFactory $factory,
        private readonly ExceptionResponseNormalizer $normalizer,
    ) {
    }

    public function handle(Throwable $throwable, ResponseInterface $response): MessageInterface|ResponseInterface
    {
        $additional = $this->factory->make($this->request, $throwable);

        $type = $additional->thrown->type;
        $message = sprintf('<general> %s', $additional->message);
        $context = $additional->context();
        match ($type) {
            ThrowableType::INVALID_INPUT => $this->logger->debug($message, $context),
            ThrowableType::FALLBACK_REQUIRED => $this->logger->info($message, $context),
            ThrowableType::RETRY_AVAILABLE => $this->logger->warning($message, $context),
            ThrowableType::UNRECOVERABLE => $this->logger->error($message, $context),
            default => $this->logger->alert($message, $context),
        };

        $code = $this->normalizer->normalizeStatusCode($throwable);
        $type = $this->normalizer->detectType($type);
        $value = $this->normalizer->normalizeBody($type, $additional->thrown->resume());
        $contents = $this->formatter->format($value, $type);
        return $response->withStatus($code)
            ->withBody(new SwooleStream($contents));
    }

    public function isValid(Throwable $throwable): bool
    {
        /** @var array<string> $ignored */
        $ignored = arrayify($this->config->get('exception.ignore', []));
        $haystack = array_map(fn (mixed $candidate) => stringify($candidate), $ignored);
        return ! in_array($throwable::class, $haystack, true);
    }
}
