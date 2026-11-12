<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Engine\Channel;
use Psr\Log\LoggerInterface;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionChecker;
use Serendipity\Infrastructure\Database\Relational\ConnectionFactory;
use Throwable;

use function Hyperf\Coroutine\defer;
use function Hyperf\Coroutine\go;

final class PdoConnectionChecker extends HyperfConnectionChecker
{
    public function __construct(
        private readonly ConnectionFactory $factory,
        private readonly ?LoggerInterface $logger = null,
        private readonly string $connection = 'default',
    ) {
    }

    public function check(int $maxAttempts = 5, int $microseconds = 1000): int
    {
        if (! $this->isAvailable()) {
            $this->logger?->debug('Attempt to check server info failed', [
                'attempts' => 1,
                'microseconds' => 0,
                'max' => 1,
            ]);
        }

        return 1;
    }

    public function isAvailable(): bool
    {
        $result = new Channel(1);
        $id = go(function () use ($result): void {
            $available = false;
            defer(static function () use ($result, &$available): void {
                $result->push($available);
            });
            try {
                $this->factory->make($this->connection)->query('SELECT 1');
                $available = true;
            } catch (Throwable) {
            }
        });

        return $id !== false && $result->pop() === true;
    }
}
