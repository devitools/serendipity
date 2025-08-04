<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\Output;

use Constructo\Testing\FakerExtension;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Output\NoContent;

final class NoContentTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;

    public function testShouldHaveNoContent(): void
    {
        $word = $this->generator()
            ->word();
        $properties = ['word' => $word];
        $output = NoContent::createFrom($properties);
        $this->assertNull($output->content());
        $this->assertEquals(
            $properties,
            $output->properties()
                ->toArray()
        );
    }
}
