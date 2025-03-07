<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Testing\Observability;

use Psr\Log\LoggerInterface;
use Stringable;

use function Serendipity\Type\Cast\stringify;

class MemoryLoggerFactory
{
    public function make(): LoggerInterface
    {
        return new class implements LoggerInterface {
            public function emergency(Stringable|string $message, array $context = []): void
            {
                $this->log('emergency', $message, $context);
            }

            public function alert(Stringable|string $message, array $context = []): void
            {
                $this->log('alert', $message, $context);
            }

            public function critical(Stringable|string $message, array $context = []): void
            {
                $this->log('critical', $message, $context);
            }

            public function error(Stringable|string $message, array $context = []): void
            {
                $this->log('error', $message, $context);
            }

            public function warning(Stringable|string $message, array $context = []): void
            {
                $this->log('warning', $message, $context);
            }

            public function notice(Stringable|string $message, array $context = []): void
            {
                $this->log('notice', $message, $context);
            }

            public function info(Stringable|string $message, array $context = []): void
            {
                $this->log('info', $message, $context);
            }

            public function debug(Stringable|string $message, array $context = []): void
            {
                $this->log('debug', $message, $context);
            }

            public function log($level, Stringable|string $message, array $context = []): void
            {
                MemoryLogger::log(stringify($level), (string) $message, $context);
            }
        };
    }
}
