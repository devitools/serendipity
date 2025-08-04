<?php

declare(strict_types=1);

namespace Serendipity\Type\Cast;

if (! function_exists(__NAMESPACE__ . '\arrayify')) {
    /**
     * @param array<T, U> $default
     * @return array<T, U>
     * @deprecated Use \Constructo\Cast\arrayify instead.
     * @template T of array-key
     * @template U
     */
    function arrayify(mixed $value, array $default = []): array
    {
        return \Constructo\Cast\arrayify($value, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\mapify')) {
    /**
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     * @deprecated Use \Constructo\Cast\mapify instead.
     */
    function mapify(mixed $data, array $default = []): array
    {
        return \Constructo\Cast\mapify($data, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\stringify')) {
    /**
     * @deprecated Use \Constructo\Cast\stringify instead.
     */
    /**
     * @deprecated Use \Constructo\Cast\stringify instead.
     */
    function stringify(mixed $value, string $default = ''): string
    {
        return \Constructo\Cast\stringify($value, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\integerify')) {
    /**
     * @deprecated Use \Constructo\Cast\integerify instead.
     */
    function integerify(mixed $value, int $default = 0): int
    {
        return \Constructo\Cast\integerify($value, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\floatify')) {
    /**
     * @deprecated Use \Constructo\Cast\floatify instead.
     */
    function floatify(mixed $value, float $default = 0.0): float
    {
        return \Constructo\Cast\floatify($value, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\boolify')) {
    /**
     * @deprecated Use \Constructo\Cast\boolify instead.
     */
    function boolify(mixed $value, bool $default = false): bool
    {
        return \Constructo\Cast\boolify($value, $default);
    }
}

namespace Serendipity\Crypt;

use Constructo\Support\Set;

use const DEFAULT_CRYPT_KEY;

if (! function_exists(__NAMESPACE__ . '\encrypt')) {
    /**
     * @deprecated Use \Constructo\Crypt\encrypt instead.
     */
    function encrypt(string $plaintext, string $key = DEFAULT_CRYPT_KEY): string
    {
        return \Constructo\Crypt\encrypt($plaintext, $key);
    }
}

if (! function_exists(__NAMESPACE__ . '\decrypt')) {
    /**
     * @deprecated Use \Constructo\Crypt\decrypt instead.
     */
    function decrypt(string $encrypted, string $key = DEFAULT_CRYPT_KEY): string
    {
        return \Constructo\Crypt\decrypt($encrypted, $key);
    }
}

if (! function_exists(__NAMESPACE__ . '\group')) {
    /**
     * @deprecated Use \Constructo\Crypt\group instead.
     */
    function group(string $algo, string $salt, string $ciphertext): string
    {
        return \Constructo\Crypt\group($algo, $salt, $ciphertext);
    }
}

if (! function_exists(__NAMESPACE__ . '\ungroup')) {
    /**
     * @deprecated Use \Constructo\Crypt\ungroup instead.
     */
    function ungroup(string $encrypted): Set
    {
        return \Constructo\Crypt\ungroup($encrypted);
    }
}

namespace Serendipity\Type\Json;

if (! function_exists(__NAMESPACE__ . '\decode')) {
    /**
     * @deprecated Use \Constructo\Json\decode instead.
     */
    function decode(string $json): ?array
    {
        return \Constructo\Json\decode($json);
    }
}

if (! function_exists(__NAMESPACE__ . '\encode')) {
    /**
     * @deprecated Use \Constructo\Json\encode instead.
     */
    function encode(array $data): ?string
    {
        return \Constructo\Json\encode($data);
    }
}

namespace Serendipity\Notation;

use Constructo\Support\Reflective\Notation;

if (! function_exists(__NAMESPACE__ . '\format')) {
    /**
     * @deprecated Use \Constructo\Notation\format instead.
     */
    function format(string $string, Notation $notation): string
    {
        return \Constructo\Notation\format($string, $notation);
    }
}

if (! function_exists(__NAMESPACE__ . '\snakify')) {
    /**
     * @deprecated Use \Constructo\Notation\snakify instead.
     */
    function snakify(string $string, bool $includeDigits = true): string
    {
        return \Constructo\Notation\snakify($string, $includeDigits);
    }
}

if (! function_exists(__NAMESPACE__ . '\camelify')) {
    /**
     * @deprecated Use \Constructo\Notation\camelify instead.
     */
    function camelify(string $string): string
    {
        return \Constructo\Notation\camelify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\pascalify')) {
    /**
     * @deprecated Use \Constructo\Notation\pascalify instead.
     */
    function pascalify(string $string): string
    {
        return \Constructo\Notation\pascalify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\adaify')) {
    /**
     * @deprecated Use \Constructo\Notation\adaify instead.
     */
    function adaify(string $string): string
    {
        return \Constructo\Notation\adaify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\macroify')) {
    /**
     * @deprecated Use \Constructo\Notation\macroify instead.
     */
    function macroify(string $string): string
    {
        return \Constructo\Notation\macroify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\kebabify')) {
    /**
     * @deprecated Use \Constructo\Notation\kebabify instead.
     */
    function kebabify(string $string): string
    {
        return \Constructo\Notation\kebabify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\trainify')) {
    /**
     * @deprecated Use \Constructo\Notation\trainify instead.
     */
    function trainify(string $string): string
    {
        return \Constructo\Notation\trainify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\cobolify')) {
    /**
     * @deprecated Use \Constructo\Notation\cobolify instead.
     */
    function cobolify(string $string): string
    {
        return \Constructo\Notation\cobolify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\lowerify')) {
    /**
     * @deprecated Use \Constructo\Notation\lowerify instead.
     */
    function lowerify(string $string): string
    {
        return \Constructo\Notation\lowerify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\upperify')) {
    /**
     * @deprecated Use \Constructo\Notation\upperify instead.
     */
    function upperify(string $string): string
    {
        return \Constructo\Notation\upperify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\titlelify')) {
    /**
     * @deprecated Use \Constructo\Notation\titlelify instead.
     */
    function titlelify(string $string): string
    {
        return \Constructo\Notation\titlelify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\sentencify')) {
    /**
     * @deprecated Use \Constructo\Notation\sentencify instead.
     */
    function sentencify(string $string): string
    {
        return \Constructo\Notation\sentencify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\dotify')) {
    /**
     * @deprecated Use \Constructo\Notation\dotify instead.
     */
    function dotify(string $string): string
    {
        return \Constructo\Notation\dotify($string);
    }
}

namespace Serendipity\Type\Util;

if (! function_exists(__NAMESPACE__ . '\extractArray')) {
    /**
     * @param array<string, array<T, U>> $array
     * @param array<T, U> $default
     * @return array<T, U>
     * @deprecated Use \Constructo\Util\extractArray instead.
     * @template T
     * @template U
     */
    function extractArray(array $array, string $property, array $default = []): array
    {
        return \Constructo\Util\extractArray($array, $property, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\extractString')) {
    /**
     * @param array<string, mixed> $array
     * @deprecated Use \Constructo\Util\extractString instead.
     */
    function extractString(array $array, string $property, string $default = ''): string
    {
        return \Constructo\Util\extractString($array, $property, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\extractInt')) {
    /**
     * @param array<string, mixed> $array
     * @deprecated Use \Constructo\Util\extractInt instead.
     */
    function extractInt(array $array, string $property, int $default = 0): int
    {
        return \Constructo\Util\extractInt($array, $property, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\extractBool')) {
    /**
     * @param array<string, mixed> $array
     * @deprecated Use \Constructo\Util\extractBool instead.
     */
    function extractBool(array $array, string $property, bool $default = false): bool
    {
        return \Constructo\Util\extractBool($array, $property, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\extractNumeric')) {
    /**
     * @param array<string, mixed> $array
     * @deprecated Use \Constructo\Util\extractInt instead.
     */
    function extractNumeric(array $array, string $property, float|int $default = 0): float
    {
        return \Constructo\Util\extractNumeric($array, $property, $default);
    }
}
