<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Stub\Type;

use Closure;
use Serendipity\Domain\Contract\Testing\Faker;
use Serendipity\Domain\Support\Reflective\Definition\TypeExtended;
use Serendipity\Domain\Support\Value;

use function Serendipity\Crypt\decrypt;
use function Serendipity\Crypt\encrypt;
use function Serendipity\Type\Cast\stringify;

class Sensitive implements TypeExtended
{
    public function build(mixed $value, Closure $build): string
    {
        return decrypt(stringify($value));
    }

    public function demolish(mixed $value, Closure $build): string
    {
        return encrypt(stringify($value));
    }

    public function fake(Faker $faker): ?Value
    {
        $value = $faker->generate(
            'regexify',
            ['/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+<>?]).{8,}$']
        );
        return new Value(encrypt($value));
    }
}
