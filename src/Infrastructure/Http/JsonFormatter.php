<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Http;

use Constructo\Contract\Formatter;
use InvalidArgumentException;
use Throwable;

use function json_encode;
use function sprintf;

class JsonFormatter implements Formatter
{
    public function format(mixed $value, mixed $option = null): string
    {
        try {
            $type = $this->type($option);
            $parsed = $this->parse($value, $type);
            return json_encode($parsed, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            return sprintf('{"status":"error","message":"%s"}', addslashes($exception->getMessage()));
        }
    }

    private function type(mixed $option): ?ResponseType
    {
        if ($option === null || $option instanceof ResponseType) {
            return $option;
        }
        throw new InvalidArgumentException(sprintf("The 'option' must be an instance of '%s'.", ResponseType::class));
    }

    private function parse(mixed $value, ?ResponseType $type = null): array
    {
        return match ($type) {
            ResponseType::FAIL => [
                'status' => 'fail',
                'data' => $value,
            ],
            ResponseType::ERROR => [
                'status' => 'error',
                'message' => $value,
            ],
            default => [
                'status' => 'success',
                'data' => $value,
            ],
        };
    }
}
