<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Document;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Serendipity\Hyperf\Database\Document\HyperfSleekDBFactory;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;

final class HyperfSleekDBFactoryTest extends TestCase
{
    use MakeExtension;

    public function testShouldCreateSleekDB(): void
    {
        $options = $this->make(ConfigInterface::class)
            ->get('databases.sleek');

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('databases.sleek')
            ->willReturn($options);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(ConfigInterface::class)
            ->willReturn($config);

        $factory = new HyperfSleekDBFactory($container);
        $factory->make('games');
    }
}
