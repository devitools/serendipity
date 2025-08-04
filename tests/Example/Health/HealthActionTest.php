<?php

declare(strict_types=1);

namespace Serendipity\Test\Example\Health;

use Constructo\Testing\FakerExtension;
use Serendipity\Example\Health\HealthAction;
use Serendipity\Example\Health\HealthInput;
use Serendipity\Hyperf\Testing\Extension\InputExtension;
use Serendipity\Hyperf\Testing\Extension\LoggerExtension;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Test\Testing\ExtensibleCase;

final class HealthActionTest extends ExtensibleCase
{
    use MakeExtension;
    use InputExtension;
    use FakerExtension;
    use LoggerExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInput();
        $this->setUpLogger();
    }

    public function testHealthAction(): void
    {
        $message = $this->generator()
            ->word();
        $input = $this->input(HealthInput::class, ['message' => $message]);
        $action = $this->make(HealthAction::class);
        $result = $action($input);
        $this->assertEquals($message, $result);
        $this->assertLogged('/Health action message using \w+: .*/', 'emergency');
        $this->assertLogged('/Health action message using \w+: .*/', 'alert');
        $this->assertLogged('/Health action message using \w+: .*/', 'critical');
        $this->assertLogged('/Health action message using \w+: .*/', 'error');
        $this->assertLogged('/Health action message using \w+: .*/', 'warning');
        $this->assertLogged('/Health action message using \w+: .*/', 'notice');
        $this->assertLogged('/Health action message using \w+: .*/', 'info');
        $this->assertLogged('/Health action message using \w+: .*/', 'debug');
    }
}
