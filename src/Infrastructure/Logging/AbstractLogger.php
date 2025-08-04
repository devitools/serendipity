<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Logging;

use Mustache_Engine;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

use function Constructo\Cast\stringify;

abstract class AbstractLogger implements LoggerInterface
{
    final public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    final public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    final public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    final public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    final public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    final public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    final public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    final public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    protected function message(string $template, Stringable|string $message, array $variables): string
    {
        $scape = fn (mixed $value): string => stringify($value);
        $engine = new Mustache_Engine(['escape' => $scape]);
        return stringify(
            $engine->render(
                $template,
                [
                    ...$variables,
                    'message' => $message,
                ]
            )
        );
    }
}
