<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub;

use Serendipity\Test\Testing\Stub\Type\BackedEnumeration;
use Serendipity\Test\Testing\Stub\Type\Enumeration;

class EnumVariety
{
    public function __construct(
        public readonly Enumeration $enum,
        public readonly BackedEnumeration $backed,
        public readonly BackedEnumeration|Enumeration $union,
        public readonly BackedEnumeration&Enumeration $intersection,
    ) {
    }
}
