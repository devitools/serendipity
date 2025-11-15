<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Testing\Extension;

use PHPUnit\Framework\Constraint\IsTrue;
use Serendipity\Hyperf\Testing\Observability\Logger\InMemory\Memory;
use Serendipity\Hyperf\Testing\Observability\Logger\InMemory\Record;

use function Constructo\Json\encode;

/**
 * @phpstan-ignore trait.unused
 */
trait LoggerExtension
{
    private bool $isLoggerSetup = false;

    abstract public static function fail(string $message = ''): never;

    protected function setUpLogger(): void
    {
        $this->registerTearDown(fn () => $this->tearDownLogger());
        $this->isLoggerSetup = true;
    }

    /**
     * @SuppressWarnings(StaticAccess)
     */
    protected function assertLogged(?string $pattern = null, ?string $level = null): void
    {
        if (! $this->isLoggerSetup) {
            static::fail('Request is not set up.');
        }

        $tallied = $this->tally($pattern, $level);
        $filters = [
            'pattern' => $pattern,
            'level' => $level,
        ];
        $message = sprintf(
            "Expected to find at least one item in logs with level '%s' using '%s'",
            $level,
            encode($filters),
        );
        static::assertThat($tallied > 0, new IsTrue(), $message);
    }

    abstract protected function registerTearDown(callable $callback): void;

    /**
     * @SuppressWarnings(StaticAccess)
     */
    private function tearDownLogger(): void
    {
        Memory::clear();
        $this->isLoggerSetup = false;
    }

    /**
     * @SuppressWarnings(StaticAccess)
     */
    private function tally(?string $pattern, ?string $level): int
    {
        $where = fn (Record $record) => ($pattern === null || preg_match($pattern, $record->message))
            && ($level === null || $record->level === $level);
        return Memory::tally($where);
    }
}
