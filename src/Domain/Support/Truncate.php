<?php

declare(strict_types=1);

namespace Serendipity\Domain\Support;

enum Truncate
{
    case BOTH;
    case BEFORE;
    case AFTER;
    case NONE;
}
