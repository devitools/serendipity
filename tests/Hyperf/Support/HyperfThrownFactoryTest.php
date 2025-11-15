<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Support;

use Exception;
use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Exception\ThrowableType;
use Serendipity\Hyperf\Support\HyperfThrownFactory;

class HyperfThrownFactoryTest extends TestCase
{
    public function testMake(): void
    {
        $classification = [Exception::class => ThrowableType::INVALID_INPUT];
        $configMock = $this->createMock(ConfigInterface::class);
        $configMock->method('get')
            ->with('exceptions.classification')
            ->willReturn($classification);

        $factory = new HyperfThrownFactory($configMock);

        $thrown = $factory->make(new Exception());
        $this->assertEquals(ThrowableType::INVALID_INPUT, $thrown->type);
    }
}
