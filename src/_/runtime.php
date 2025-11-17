<?php

declare(strict_types=1);

namespace Serendipity\Runtime;

use Hyperf\Coroutine\Coroutine;
use Psr\EventDispatcher\EventDispatcherInterface;

use function Hyperf\Support\make;

if (! function_exists(__NAMESPACE__ . '\invoke')) {
    function invoke(callable $callback, mixed ...$args): mixed
    {
        return $callback(...$args);
    }
}

/** @SuppressWarnings(StaticAccess) */
if (! function_exists(__NAMESPACE__ . '\coroutine')) {
    function coroutine(callable $callback): int
    {
        return Coroutine::create($callback);
    }
}

if (! function_exists(__NAMESPACE__ . '\dispatch')) {
    function dispatch(object $event): void
    {
        $dispatcher = make(EventDispatcherInterface::class);
        $dispatcher->dispatch($event);
    }
}
