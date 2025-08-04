<?php

declare(strict_types=1);

namespace Serendipity\Domain\Support\Reflective\Definition;

use Closure;
use Constructo\Support\Value;
use Serendipity\Domain\Contract\Testing\Faker;

interface TypeExtended
{
    public function build(mixed $value, Closure $build): mixed;

    public function demolish(mixed $value, Closure $demolish): mixed;

    public function fake(Faker $faker): ?Value;
}
