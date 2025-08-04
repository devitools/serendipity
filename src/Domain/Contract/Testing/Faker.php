<?php

declare(strict_types=1);

namespace Serendipity\Domain\Contract\Testing;

use Constructo\Support\Set;

interface Faker
{
    public function generate(string $name, array $arguments = []): mixed;

    /**
     * @template U of object
     * @param class-string<U> $class
     */
    public function fake(string $class, array $presets = []): Set;
}
