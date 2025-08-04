<?php

declare(strict_types=1);

namespace Serendipity\Test;

use PHPUnit\Framework\TestCase;
use Serendipity\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    public function testBeSuccessfully(): void
    {
        $provider = new ConfigProvider();
        $config = $provider();
        $this->assertArrayHasKey('commands', $config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('annotations', $config);
    }
}
