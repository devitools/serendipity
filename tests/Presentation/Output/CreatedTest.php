<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\Output;

use Constructo\Testing\FakerExtension;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Output\Created;

final class CreatedTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;

    public function testShouldHaveIdOnContent(): void
    {
        $id = $this->generator()
            ->uuid();
        $output = Created::createFrom($id);
        $this->assertEquals($id, $output->content());
    }
}
