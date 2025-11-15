<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Http;

use Hyperf\Validation\ValidationException;
use Serendipity\Domain\Exception\InvalidInputException;
use Serendipity\Domain\Exception\ThrowableType;
use Throwable;

use function Constructo\Cast\integerify;
use function Constructo\Json\decode;

class ExceptionResponseNormalizer
{
    public function normalizeStatusCode(Throwable $throwable, int $fallback = 500): int
    {
        $code = match (true) {
            $throwable instanceof ValidationException => $throwable->status,
            $throwable instanceof InvalidInputException => 428,
            default => integerify($throwable->getCode()),
        };
        return ($code < 400 || $code > 599)
            ? $fallback
            : $code;
    }

    public function detectType(ThrowableType $type): ResponseType
    {
        return match ($type) {
            ThrowableType::INVALID_INPUT,
            ThrowableType::FALLBACK_REQUIRED,
            ThrowableType::RETRY_AVAILABLE => ResponseType::FAIL,
            ThrowableType::UNRECOVERABLE,
            ThrowableType::UNTREATED => ResponseType::ERROR,
        };
    }

    public function normalizeBody(ResponseType $type, string $message): array|string|null
    {
        $data = decode($message);
        return match ($type) {
            ResponseType::FAIL => $data ?? ['message' => $message],
            ResponseType::ERROR => $message,
            default => null,
        };
    }
}
