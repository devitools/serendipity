<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Formatter;

use Constructo\Contract\Formatter;

use function Constructo\Json\encode;
use function is_array;
use function is_string;

class RelationalArrayToJson implements Formatter
{
    public function format(mixed $value, mixed $option = null): ?string
    {
        if (is_string($value)) {
            return $value;
        }
        if (! is_array($value)) {
            return null;
        }
        return encode($value);
    }
}
