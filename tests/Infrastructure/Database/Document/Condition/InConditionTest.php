<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Database\Document\Condition;

use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Database\Document\Mongo\Condition\InCondition;

class InConditionTest extends TestCase
{
    public function testShouldCompose(): void
    {
        $condition = new InCondition();
        $composed = $condition->compose('A,B');
        $this->assertEquals(
            [
                '$in' => [
                    'A',
                    'B',
                ],
            ],
            $composed
        );
    }
}
