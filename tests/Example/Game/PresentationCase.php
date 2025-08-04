<?php

declare(strict_types=1);

namespace Serendipity\Test\Example\Game;

use Constructo\Testing\FakerExtension;
use Serendipity\Hyperf\Testing\Extension\InputExtension;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Hyperf\Testing\PostgresHelper;
use Serendipity\Hyperf\Testing\SleekDBHelper;
use Serendipity\Test\Testing\ExtensibleCase;
use Serendipity\Testing\Extension\ResourceExtension;

abstract class PresentationCase extends ExtensibleCase
{
    use MakeExtension;
    use FakerExtension;
    use ResourceExtension;
    use InputExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInput();

        $this->setUpResourceHelper('sleek', $this->make(SleekDBHelper::class));
        $this->setUpResourceHelper('postgres', $this->make(PostgresHelper::class));
    }
}
