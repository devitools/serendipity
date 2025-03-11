<?php

declare(strict_types=1);

use function Serendipity\Type\Cast\arrayify;
use function Serendipity\Type\Cast\integerify;
use function Serendipity\Type\Util\extractInt;

if (! function_exists('array_flatten')) {
    function array_flatten(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result += array_flatten($value);
                continue;
            }
            $result[$key] = $value;
        }
        return $result;
    }
}

if (! function_exists('array_flatten_prefixed')) {
    function array_flatten_prefixed(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result += array_flatten_prefixed($value, $prefix . $key . '.');
                continue;
            }
            $result[$prefix . $key] = $value;
        }
        return $result;
    }
}

if (! function_exists('array_shift_pluck_int')) {
    function array_shift_pluck_int(mixed $array, string $property): ?int
    {
        $data = arrayify($array);
        if (empty($data)) {
            return null;
        }
        $datum = arrayify($data[0]);
        $id = integerify(extractInt($datum, $property));
        return $id === 0 ? null : $id;
    }
}

if (! function_exists('array_first')) {
    function array_first(array $array): mixed
    {
        return empty($array)
            ? null
            : $array[0];
    }
}

if (! function_exists('array_unshift_key')) {
    function array_unshift_key(array $array, string $key, mixed $value): array
    {
        if (! is_array($array[$key] ?? null)) {
            $array[$key] = [];
        }
        $array[$key][] = $value;
        return $array;
    }
}
