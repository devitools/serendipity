<?php

declare(strict_types=1);

namespace Serendipity\Test\Unit\Presentation\Output;

use Serendipity\Infrastructure\Testing\TestCase;
use Serendipity\Presentation\Output\NotFound;

/**
 * @internal
 * @coversNothing
 */
class NotFoundTest extends TestCase
{
    public function testShouldHaveMissingOnContent(): void
    {
        $missing = $this->faker->faker->word();
        $what = $this->faker->faker->uuid();
        $properties = ['Missing' => sprintf('"%s" identified by "%s" not found', $missing, $what)];
        $output = new NotFound($missing, $what);
        $this->assertNull($output->content());
        $this->assertEquals($properties, $output->properties()->toArray());
    }
}
