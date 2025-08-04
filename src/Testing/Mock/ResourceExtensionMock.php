<?php

declare(strict_types=1);

namespace Serendipity\Testing\Mock;

use Closure;
use Constructo\Support\Set;
use PHPUnit\Framework\Constraint\Constraint;
use Serendipity\Domain\Contract\Testing\Helper;
use Serendipity\Testing\Extension\ResourceExtension;
use Serendipity\Testing\FailException;

final class ResourceExtensionMock
{
    use ResourceExtension;

    private static Closure $assert;

    private array $registeredTearDowns = [];

    public function __construct(Closure $assert)
    {
        self::$assert = $assert;
    }

    public function exposeSetUpResourceHelper(string $alias, Helper $helper): void
    {
        $this->setUpResourceHelper($alias, $helper);
    }

    public function exposeSetUpResource(string $resource, string $alias): void
    {
        $this->setUpResource($resource, $alias);
    }

    public function exposeSeed(string $type, array $override = [], ?string $resource = null): Set
    {
        return $this->seed($type, $override, $resource);
    }

    public function exposeAssertHas(array $filters, ?string $resource = null): void
    {
        $this->assertHas($filters, $resource);
    }

    public function exposeAssertHasNot(array $filters, ?string $resource = null): void
    {
        $this->assertHasNot($filters, $resource);
    }

    public function exposeAssertHasExactly(int $expected, array $filters, ?string $resource = null): void
    {
        $this->assertHasExactly($expected, $filters, $resource);
    }

    /**
     * @return array<string,Helper>
     */
    public function getHelpers(): array
    {
        return $this->helpers;
    }

    /**
     * @return array<string,Helper>
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    public function getRegisteredTearDowns(): array
    {
        return $this->registeredTearDowns;
    }

    public static function assertThat(mixed $value, Constraint $constraint, string $message = ''): void
    {
        call_user_func(self::$assert, $value, $constraint, $message);
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

    protected function registerTearDown(callable $callback): void
    {
        $this->registeredTearDowns[] = $callback;
    }
}
