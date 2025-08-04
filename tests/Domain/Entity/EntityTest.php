<?php

declare(strict_types=1);

namespace Serendipity\Test\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Constructo\Support\Entity;

final class EntityTest extends TestCase
{
    public function testShouldExposeValues(): void
    {
        $entity = new class extends Entity {
            protected string $value = 'none';
        };

        $this->assertEquals((object) ['value' => 'none'], $entity->jsonSerialize());
    }
}
