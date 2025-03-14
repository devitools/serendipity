<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Testing\Observability\Logger\InMemory;

use Psr\Log\LoggerInterface;
use Stringable;

use function Serendipity\Type\Cast\stringify;

final class InMemoryLogger implements LoggerInterface
{
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * @SuppressWarnings(StaticAccess)
     * @param mixed $level
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        Memory::write(stringify($level), (string) $message, $context);
    }
}
