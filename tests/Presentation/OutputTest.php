<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation;

use PHPUnit\Framework\TestCase;
use Serendipity\Presentation\Output;

class OutputTest extends TestCase
{
    public function testShouldHasPropertiesAsEmptyArrayAndValuesNull(): void
    {
        $output = new Output();
        $this->assertEquals(
            [],
            $output->properties()
                ->toArray()
        );
        $this->assertNull($output->content());
    }

    public function testShouldHasProperties(): void
    {
        $output = new Output(null, ['foo' => 'bar']);
        $this->assertEquals(
            ['foo' => 'bar'],
            $output->properties()
                ->toArray()
        );
    }

    public function testShouldHasValues(): void
    {
        $output = new Output(content: ['foo' => 'bar'], properties: ['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $output->content());
    }
}
