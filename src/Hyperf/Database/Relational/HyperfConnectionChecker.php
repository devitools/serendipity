<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational;

use PDO;
use Psr\Log\LoggerInterface;
use Serendipity\Infrastructure\Database\Relational\ConnectionChecker;
use Throwable;

class HyperfConnectionChecker implements ConnectionChecker
{
    public function __construct(
        private readonly HyperfConnection $database,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $message = 'Attempt to check server info failed',
    ) {
    }

    public function check(int $maxAttempts = 5, int $microseconds = 1000): int
    {
        $attempts = 0;
        do {
            ++$attempts;
            if ($this->isAvailable()) {
                return $attempts;
            }
            $this->debug($attempts, $microseconds, $maxAttempts);
            usleep($microseconds);
        } while ($attempts < $maxAttempts);
        return $attempts;
    }

    public function isAvailable(): bool
    {
        try {
            $this->database->run(fn (PDO $pdo) => $pdo->getAttribute(PDO::ATTR_SERVER_INFO));
        } catch (Throwable) {
            return false;
        }
        return true;
    }

    private function debug(int $attempts, int $microseconds, int $maxAttempts): void
    {
        if (! isset($this->logger, $this->message)) {
            return;
        }
        $this->logger->debug($this->message, $this->context($attempts, $microseconds, $maxAttempts));
    }

    private function context(int $attempts, int $microseconds, int $max): array
    {
        return [
            'attempts' => $attempts,
            'microseconds' => $microseconds,
            'max' => $max,
        ];
    }
}
