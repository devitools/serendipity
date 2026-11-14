<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Admission;

use function hrtime;

class MonotonicClock
{
    public function now(): float
    {
        return hrtime(true) / 1_000_000_000;
    }
}
