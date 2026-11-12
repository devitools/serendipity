<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Database\DetectsLostConnections;
use PDOException;
use Throwable;

class PdoFailureClassifier
{
    use DetectsLostConnections;

    public function isConnectionFailure(Throwable $exception): bool
    {
        if (! $exception instanceof PDOException) {
            return false;
        }
        $state = $this->state($exception);
        if ($this->isTransportState($state)) {
            return true;
        }

        return in_array($state, ['', '0', 'HY000'], true) && $this->causedByLostConnection($exception);
    }

    private function isTransportState(string $state): bool
    {
        return str_starts_with($state, '08') || in_array($state, ['57P01', '57P02', '57P03'], true);
    }

    private function state(PDOException $exception): string
    {
        $state = $exception->errorInfo[0] ?? null;
        return is_string($state) ? $state : (string) $exception->getCode();
    }
}
