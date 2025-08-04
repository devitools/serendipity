<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Formatter;

use Constructo\Contract\Formatter;

use function is_array;
use function is_string;
use function Serendipity\Type\Json\encode;

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
