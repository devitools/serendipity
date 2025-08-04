<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Testing\Mock;

use Closure;
use PHPUnit\Framework\Constraint\Constraint;
use Serendipity\Hyperf\Testing\Extension\LoggerExtension;
use Serendipity\Testing\FailException;

final class LoggerExtensionMock
{
    use LoggerExtension;

    private array $registeredTearDowns = [];

    private static ?Closure $assertion = null;

    public function __construct(?Closure $assertion = null)
    {
        if ($assertion !== null) {
            self::$assertion = $assertion;
        }
    }

    public function exposeSetUpLogger(): void
    {
        $this->setUpLogger();
    }

    public function exposeTearDownLogger(): void
    {
        $this->tearDownLogger();
    }

    public function exposeAssertLogged(?string $pattern = null, ?string $level = null): void
    {
        $this->assertLogged($pattern, $level);
    }

    public function exposeTally(?string $pattern, ?string $level): int
    {
        return $this->tally($pattern, $level);
    }

    public function getRegisteredTearDowns(): array
    {
        return $this->registeredTearDowns;
    }

    public function getIsLoggerSetup(): bool
    {
        return $this->isLoggerSetup;
    }

    /**
     * @throws FailException
     */
    public static function fail(string $message = ''): never
    {
        throw new FailException(
            $message
                ?: 'Test failure'
        );
    }

    public static function assertThat(mixed $value, Constraint $constraint, string $message = ''): void
    {
        if (self::$assertion !== null) {
            call_user_func(self::$assertion, $value, $constraint, $message);
        }
    }

    protected function registerTearDown(callable $callback): void
    {
        $this->registeredTearDowns[] = $callback;
    }
}
