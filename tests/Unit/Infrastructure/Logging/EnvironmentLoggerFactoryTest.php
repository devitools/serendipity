<?php

declare(strict_types=1);

namespace Serendipity\Test\Unit\Infrastructure\Logging;

use Serendipity\Infrastructure\Logging\EnvironmentLoggerFactory;
use Serendipity\Infrastructure\Testing\TestCase;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;

/**
 * @internal
 * @coversNothing
 */
class EnvironmentLoggerFactoryTest extends TestCase
{
    private StdoutLoggerInterface $stdoutLogger;

    private ConfigInterface $config;

    private EnvironmentLoggerFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stdoutLogger = $this->createMock(StdoutLoggerInterface::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->factory = new EnvironmentLoggerFactory($this->stdoutLogger, $this->config);
    }

    public function testShouldReturnStdoutLoggerForDevEnv(): void
    {
        $logger = $this->factory->make('dev');

        $this->assertSame($this->stdoutLogger, $logger);
    }

    public function testShouldReturnGcloudLoggerForNonDevEnv(): void
    {
        $this->config->method('get')
            ->willReturn(false);

        $logger = $this->factory->make('prd');

        $this->assertNotSame($this->stdoutLogger, $logger);
    }

    public function testShouldReturnBatchGcloudLoggerWhenConfigured(): void
    {
        $this->config->method('get')
            ->with('logger.gcloud.batch', false)
            ->willReturn(true);

        $logger = $this->factory->make('prd');

        $this->assertNotSame($this->stdoutLogger, $logger);
    }
}
