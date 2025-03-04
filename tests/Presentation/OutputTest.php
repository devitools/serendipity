<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation;

use PHPUnit\Framework\TestCase;
use Serendipity\Presentation\Output;

/**
 * @internal
 */
class OutputTest extends TestCase
{
    public function testShouldHasPropertiesAsEmptyArrayAndValuesNull(): void
    {
        $output = new Output();
        $this->assertEquals([], $output->properties()->toArray());
        $this->assertNull($output->content());
    }

    public function testShouldHasProperties(): void
    {
        $output = new Output(null, ['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $output->properties()->toArray());
        $this->assertEquals($output->toArray()['properties'], $output->properties()->toArray());
        $this->assertEquals('bar', $output->property('foo'));
    }

    public function testShouldHasValues(): void
    {
        $output = new Output(content: ['foo' => 'bar'], properties: ['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $output->content());
        $this->assertEquals($output->toArray()['content'], $output->content());
    }
}
