<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo;

use RuntimeException;
use Throwable;

class PdoSessionState
{
    private ?Throwable $failure = null;

    public function __construct(private readonly PdoFailureClassifier $classifier)
    {
    }

    public function isInvalid(): bool
    {
        return $this->failure !== null;
    }

    public function guard(): void
    {
        if ($this->failure !== null) {
            throw new RuntimeException(
                'PostgreSQL session is invalid; operation was not executed. Release it before acquiring again.',
                0,
                $this->failure
            );
        }
    }

    public function recordFailure(Throwable $exception): void
    {
        if ($this->classifier->isConnectionFailure($exception)) {
            $this->invalidate($exception);
        }
    }

    public function invalidate(Throwable $exception): void
    {
        $this->failure ??= $exception;
    }
}
