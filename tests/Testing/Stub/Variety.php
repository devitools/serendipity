<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use Countable;
use Iterator;

class Variety
{
    private readonly mixed $whatever;

    public function __construct(
        public readonly int|string $union,
        public readonly Countable&Iterator $intersection,
        public readonly EntityStub $nested,
        $whatever,
    ) {
        $this->whatever = $whatever;
    }

    public function getWhatever()
    {
        return $this->whatever;
    }
}
