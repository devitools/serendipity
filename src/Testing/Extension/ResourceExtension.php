<?php

declare(strict_types=1);

namespace Serendipity\Testing\Extension;

use Constructo\Support\Set;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsTrue;
use Serendipity\Domain\Contract\Testing\Helper;

use function array_key_first;
use function count;
use function Serendipity\Type\Cast\stringify;
use function Serendipity\Type\Json\encode;
use function sprintf;

trait ResourceExtension
{
    /**
     * @var array<string,Helper>
     */
    private array $helpers = [];

    /**
     * @var array<string,Helper>
     */
    private array $resources = [];

    abstract public static function fail(string $message = ''): never;

    abstract public static function assertThat(mixed $value, Constraint $constraint, string $message = ''): void;

    protected function setUpResourceHelper(string $alias, Helper $helper): void
    {
        $this->helpers[$alias] = $helper;
    }

    protected function setUpResource(string $resource, string $alias): void
    {
        if (! isset($this->helpers[$alias])) {
            static::fail('Helper not defined');
        }

        $helper = $this->helpers[$alias];

        $this->resources[$resource] = $helper;
        $helper->truncate($resource);
        $this->registerTearDown(fn () => $helper->truncate($resource));
    }

    protected function seed(string $type, array $override = [], ?string $resource = null): Set
    {
        $resource = $this->resolveResource($resource);
        $helper = $this->resolveHelper($resource);
        return $helper->seed($type, $resource, $override);
    }

    protected function assertHas(array $filters, ?string $resource = null): void
    {
        $tallied = $this->tally($filters, $resource);
        $message = sprintf(
            "Expected to find at least one item in resource '%s' with filters '%s'",
            $resource,
            encode($filters),
        );
        static::assertThat($tallied > 0, new IsTrue(), $message);
    }

    protected function assertHasNot(array $filters, ?string $resource = null): void
    {
        $tallied = $this->tally($filters, $resource);
        $message = sprintf(
            "Expected to not find any item in resource '%s' with filters '%s'",
            $resource,
            encode($filters),
        );
        static::assertThat($tallied === 0, new IsTrue(), $message);
    }

    protected function assertHasExactly(int $expected, array $filters, ?string $resource = null): void
    {
        $tallied = $this->tally($filters, $resource);
        $message = sprintf(
            "Expected to find %d items in resource '%s' with filters '%s', but found %d",
            $expected,
            $resource,
            encode($filters),
            $tallied
        );
        static::assertThat($tallied === $expected, new IsTrue(), $message);
    }

    abstract protected function registerTearDown(callable $callback): void;

    private function tally(array $filters, ?string $resource): int
    {
        $resource = $this->resolveResource($resource);
        $helper = $this->resolveHelper($resource);
        return $helper->count($resource, $filters);
    }

    private function resolveHelper(string $resource): Helper
    {
        return $this->resources[$resource];
    }

    private function resolveResource(?string $resource): string
    {
        if (isset($this->resources[$resource])) {
            return stringify($resource);
        }
        if (count($this->resources) === 1) {
            return array_key_first($this->resources);
        }
        static::fail('Resource not defined');
    }
}
