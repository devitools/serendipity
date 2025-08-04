<?php

declare(strict_types=1);

namespace Serendipity\Notation;

use Constructo\Support\Reflective\Notation;
use Jawira\CaseConverter\Convert;

use function Serendipity\Type\Cast\stringify;

if (! function_exists(__NAMESPACE__ . '\format')) {
    function format(string $string, Notation $notation): string
    {
        return match ($notation) {
            Notation::SNAKE => snakify($string),
            Notation::CAMEL => camelify($string),
            Notation::PASCAL => pascalify($string),
            Notation::ADA => adaify($string),
            Notation::MACRO => macroify($string),
            Notation::KEBAB => kebabify($string),
            Notation::TRAIN => trainify($string),
            Notation::COBOL => cobolify($string),
            Notation::LOWER => lowerify($string),
            Notation::UPPER => upperify($string),
            Notation::TITLE => titlelify($string),
            Notation::SENTENCE => sentencify($string),
            Notation::DOT => dotify($string),
            Notation::NONE => $string,
        };
    }
}

if (! function_exists(__NAMESPACE__ . '\snakify')) {
    function snakify(string $string, bool $includeDigits = true): string
    {
        $string = (new Convert($string))->toSnake();
        if ($includeDigits) {
            return ltrim(stringify(preg_replace('/(\d+)/', '_$0', $string)), '_');
        }
        return $string;
    }
}

if (! function_exists(__NAMESPACE__ . '\camelify')) {
    function camelify(string $string): string
    {
        return (new Convert($string))->toCamel();
    }
}

if (! function_exists(__NAMESPACE__ . '\pascalify')) {
    function pascalify(string $string): string
    {
        return (new Convert($string))->toPascal();
    }
}

if (! function_exists(__NAMESPACE__ . '\adaify')) {
    function adaify(string $string): string
    {
        return (new Convert($string))->toAda();
    }
}

if (! function_exists(__NAMESPACE__ . '\macroify')) {
    function macroify(string $string): string
    {
        return (new Convert($string))->toMacro();
    }
}

if (! function_exists(__NAMESPACE__ . '\kebabify')) {
    function kebabify(string $string): string
    {
        return (new Convert($string))->toKebab();
    }
}

if (! function_exists(__NAMESPACE__ . '\trainify')) {
    function trainify(string $string): string
    {
        return (new Convert($string))->toTrain();
    }
}

if (! function_exists(__NAMESPACE__ . '\cobolify')) {
    function cobolify(string $string): string
    {
        return (new Convert($string))->toCobol();
    }
}

if (! function_exists(__NAMESPACE__ . '\lowerify')) {
    function lowerify(string $string): string
    {
        return (new Convert($string))->toLower();
    }
}

if (! function_exists(__NAMESPACE__ . '\upperify')) {
    function upperify(string $string): string
    {
        return (new Convert($string))->toUpper();
    }
}

if (! function_exists(__NAMESPACE__ . '\titlelify')) {
    function titlelify(string $string): string
    {
        return (new Convert($string))->toTitle();
    }
}

if (! function_exists(__NAMESPACE__ . '\sentencify')) {
    function sentencify(string $string): string
    {
        return (new Convert($string))->toSentence();
    }
}

if (! function_exists(__NAMESPACE__ . '\dotify')) {
    function dotify(string $string): string
    {
        return (new Convert($string))->toDot();
    }
}
