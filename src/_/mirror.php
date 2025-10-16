<?php

declare(strict_types=1);

namespace Serendipity\Type\Cast;

if (! function_exists(__NAMESPACE__ . '\arrayify')) {
    /**
     * @param array<T, U> $default
     * @return array<T, U>
     * @deprecated use \Constructo\Cast\arrayify instead
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
     * @deprecated use \Constructo\Cast\mapify instead
     */
    function mapify(mixed $data, array $default = []): array
    {
        return \Constructo\Cast\mapify($data, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\stringify')) {
    /**
     * @deprecated use \Constructo\Cast\stringify instead
     */
    /**
     * @deprecated use \Constructo\Cast\stringify instead
     */
    function stringify(mixed $value, string $default = ''): string
    {
        return \Constructo\Cast\stringify($value, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\integerify')) {
    /**
     * @deprecated use \Constructo\Cast\integerify instead
     */
    function integerify(mixed $value, int $default = 0): int
    {
        return \Constructo\Cast\integerify($value, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\floatify')) {
    /**
     * @deprecated use \Constructo\Cast\floatify instead
     */
    function floatify(mixed $value, float $default = 0.0): float
    {
        return \Constructo\Cast\floatify($value, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\boolify')) {
    /**
     * @deprecated use \Constructo\Cast\boolify instead
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
     * @deprecated use \Constructo\Crypt\encrypt instead
     */
    function encrypt(string $plaintext, string $key = DEFAULT_CRYPT_KEY): string
    {
        return \Constructo\Crypt\encrypt($plaintext, $key);
    }
}

if (! function_exists(__NAMESPACE__ . '\decrypt')) {
    /**
     * @deprecated use \Constructo\Crypt\decrypt instead
     */
    function decrypt(string $encrypted, string $key = DEFAULT_CRYPT_KEY): string
    {
        return \Constructo\Crypt\decrypt($encrypted, $key);
    }
}

if (! function_exists(__NAMESPACE__ . '\group')) {
    /**
     * @deprecated use \Constructo\Crypt\group instead
     */
    function group(string $algo, string $salt, string $ciphertext): string
    {
        return \Constructo\Crypt\group($algo, $salt, $ciphertext);
    }
}

if (! function_exists(__NAMESPACE__ . '\ungroup')) {
    /**
     * @deprecated use \Constructo\Crypt\ungroup instead
     */
    function ungroup(string $encrypted): Set
    {
        return \Constructo\Crypt\ungroup($encrypted);
    }
}

namespace Serendipity\Type\Json;

if (! function_exists(__NAMESPACE__ . '\decode')) {
    /**
     * @deprecated use \Constructo\Json\decode instead
     */
    function decode(string $json): ?array
    {
        return \Constructo\Json\decode($json);
    }
}

if (! function_exists(__NAMESPACE__ . '\encode')) {
    /**
     * @deprecated use \Constructo\Json\encode instead
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
     * @deprecated use \Constructo\Notation\format instead
     */
    function format(string $string, Notation $notation): string
    {
        return \Constructo\Notation\format($string, $notation);
    }
}

if (! function_exists(__NAMESPACE__ . '\snakify')) {
    /**
     * @deprecated use \Constructo\Notation\snakify instead
     */
    function snakify(string $string, bool $includeDigits = true): string
    {
        return \Constructo\Notation\snakify($string, $includeDigits);
    }
}

if (! function_exists(__NAMESPACE__ . '\camelify')) {
    /**
     * @deprecated use \Constructo\Notation\camelify instead
     */
    function camelify(string $string): string
    {
        return \Constructo\Notation\camelify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\pascalify')) {
    /**
     * @deprecated use \Constructo\Notation\pascalify instead
     */
    function pascalify(string $string): string
    {
        return \Constructo\Notation\pascalify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\adaify')) {
    /**
     * @deprecated use \Constructo\Notation\adaify instead
     */
    function adaify(string $string): string
    {
        return \Constructo\Notation\adaify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\macroify')) {
    /**
     * @deprecated use \Constructo\Notation\macroify instead
     */
    function macroify(string $string): string
    {
        return \Constructo\Notation\macroify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\kebabify')) {
    /**
     * @deprecated use \Constructo\Notation\kebabify instead
     */
    function kebabify(string $string): string
    {
        return \Constructo\Notation\kebabify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\trainify')) {
    /**
     * @deprecated use \Constructo\Notation\trainify instead
     */
    function trainify(string $string): string
    {
        return \Constructo\Notation\trainify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\cobolify')) {
    /**
     * @deprecated use \Constructo\Notation\cobolify instead
     */
    function cobolify(string $string): string
    {
        return \Constructo\Notation\cobolify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\lowerify')) {
    /**
     * @deprecated use \Constructo\Notation\lowerify instead
     */
    function lowerify(string $string): string
    {
        return \Constructo\Notation\lowerify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\upperify')) {
    /**
     * @deprecated use \Constructo\Notation\upperify instead
     */
    function upperify(string $string): string
    {
        return \Constructo\Notation\upperify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\titlelify')) {
    /**
     * @deprecated use \Constructo\Notation\titlelify instead
     */
    function titlelify(string $string): string
    {
        return \Constructo\Notation\titlelify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\sentencify')) {
    /**
     * @deprecated use \Constructo\Notation\sentencify instead
     */
    function sentencify(string $string): string
    {
        return \Constructo\Notation\sentencify($string);
    }
}

if (! function_exists(__NAMESPACE__ . '\dotify')) {
    /**
     * @deprecated use \Constructo\Notation\dotify instead
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
     * @deprecated use \Constructo\Util\extractArray instead
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
     * @deprecated use \Constructo\Util\extractString instead
     */
    function extractString(array $array, string $property, string $default = ''): string
    {
        return \Constructo\Util\extractString($array, $property, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\extractInt')) {
    /**
     * @param array<string, mixed> $array
     * @deprecated use \Constructo\Util\extractInt instead
     */
    function extractInt(array $array, string $property, int $default = 0): int
    {
        return \Constructo\Util\extractInt($array, $property, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\extractBool')) {
    /**
     * @param array<string, mixed> $array
     * @deprecated use \Constructo\Util\extractBool instead
     */
    function extractBool(array $array, string $property, bool $default = false): bool
    {
        return \Constructo\Util\extractBool($array, $property, $default);
    }
}

if (! function_exists(__NAMESPACE__ . '\extractNumeric')) {
    /**
     * @param array<string, mixed> $array
     * @deprecated use \Constructo\Util\extractInt instead
     */
    function extractNumeric(array $array, string $property, float|int $default = 0): float
    {
        return \Constructo\Util\extractNumeric($array, $property, $default);
    }
}
