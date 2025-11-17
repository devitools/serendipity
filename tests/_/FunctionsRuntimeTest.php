<?php

declare(strict_types=1);

namespace Serendipity\Test\_;

use Hyperf\Context\ApplicationContext;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use stdClass;

use function Serendipity\Runtime\coroutine;
use function Serendipity\Runtime\dispatch;
use function Serendipity\Runtime\invoke;

final class FunctionsRuntimeTest extends TestCase
{
    public function testInvokeShouldCallCallable(): void
    {
        $callable = fn (int $a, int $b): int => $a + $b;
        $this->assertEquals(3, invoke($callable, 1, 2));
    }

    public function testInvokeShouldPassMultipleArguments(): void
    {
        $callable = fn (string $a, string $b, string $c): string => $a . $b . $c;
        $this->assertEquals('abc', invoke($callable, 'a', 'b', 'c'));
    }

    public function testCoroutineShouldReturnId(): void
    {
        $this->assertIsInt(coroutine(fn () => null));
    }

    public function testDispatchShouldCallEventDispatcher(): void
    {
        $event = new class {
            public string $type = 'test-event';
        };

        $tracker = new class {
            public bool $dispatchCalled = false;

            public ?object $receivedEvent = null;
        };

        $mockDispatcher = new readonly class ($tracker) implements EventDispatcherInterface {
            public function __construct(
                private object $tracker,
            ) {
            }

            public function dispatch(object $event): object
            {
                $this->tracker->dispatchCalled = true;
                $this->tracker->receivedEvent = $event;
                return $event;
            }
        };

        $originalContainer = $this->swapContainerDispatcher($mockDispatcher);

        try {
            dispatch($event);
            $this->assertTrue($tracker->dispatchCalled, 'EventDispatcher::dispatch() should be called');
            $this->assertSame($event, $tracker->receivedEvent, 'Event should be passed to the dispatcher');
        } finally {
            $this->restoreContainer($originalContainer);
        }
    }

    public function testDispatchShouldAcceptAnyObject(): void
    {
        $event1 = new class {
            public string $type = 'event1';
        };
        $event2 = new stdClass();

        $tracker = new class {
            public int $dispatchCount = 0;

            public array $receivedEvents = [];
        };

        $mockDispatcher = new readonly class ($tracker) implements EventDispatcherInterface {
            public function __construct(
                private object $tracker,
            ) {
            }

            public function dispatch(object $event): object
            {
                ++$this->tracker->dispatchCount;
                $this->tracker->receivedEvents[] = $event;
                return $event;
            }
        };

        $originalContainer = $this->swapContainerDispatcher($mockDispatcher);

        try {
            dispatch($event1);
            dispatch($event2);
            $this->assertEquals(2, $tracker->dispatchCount, 'EventDispatcher::dispatch() should be called twice');
            $this->assertCount(2, $tracker->receivedEvents, 'Should receive both events');
            $this->assertSame($event1, $tracker->receivedEvents[0], 'The first event should match');
            $this->assertSame($event2, $tracker->receivedEvents[1], 'The second event should match');
        } finally {
            $this->restoreContainer($originalContainer);
        }
    }

    private function swapContainerDispatcher(EventDispatcherInterface $dispatcher): ?ContainerInterface
    {
        $originalContainer = null;
        if (ApplicationContext::hasContainer()) {
            $originalContainer = ApplicationContext::getContainer();
        }

        $container = new readonly class ($dispatcher) implements ContainerInterface {
            public function __construct(private EventDispatcherInterface $dispatcher)
            {
            }

            public function get(string $id): EventDispatcherInterface
            {
                return $this->dispatcher;
            }

            public function has(string $id): bool
            {
                return $id === EventDispatcherInterface::class;
            }

            public function make(string $name, array $parameters = []): EventDispatcherInterface
            {
                return $this->dispatcher;
            }
        };

        ApplicationContext::setContainer($container);

        return $originalContainer;
    }

    private function restoreContainer(?ContainerInterface $container): void
    {
        if ($container !== null) {
            ApplicationContext::setContainer($container);
        }
    }
}
