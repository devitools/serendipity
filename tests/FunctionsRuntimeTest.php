<?php

declare(strict_types=1);

namespace Serendipity\Test;

use PHPUnit\Framework\TestCase;

use function Serendipity\Runtime\coroutine;
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
}
