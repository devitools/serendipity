<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\Output;

use Constructo\Testing\FakerExtension;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Output\Accepted;

final class AcceptedTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;

    public function testShouldHaveTokenOnContent(): void
    {
        $token = $this->generator()
            ->uuid();
        $output = Accepted::createFrom($token);
        $this->assertEquals($token, $output->content());
        $this->assertEquals(['token' => $token],
            $output->properties()
                ->toArray());
    }
}
