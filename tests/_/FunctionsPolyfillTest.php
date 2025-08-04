<?php

declare(strict_types=1);

namespace Serendipity\Test\_;

use PHPUnit\Framework\TestCase;

final class FunctionsPolyfillTest extends TestCase
{
    public function testArrayFlattenShouldFlattenNestedArrays(): void
    {
        $array = [
            'a' => 1,
            'b' => [
                'c' => 2,
                'd' => 3,
            ],
            'e' => [
                'f' => [
                    'g' => 4,
                ],
            ],
        ];

        $expected = [
            'a' => 1,
            'c' => 2,
            'd' => 3,
            'g' => 4,
        ];

        $this->assertEquals($expected, array_flatten($array));
    }

    public function testArrayFlattenPrefixedShouldFlattenWithPrefixes(): void
    {
        $array = [
            'a' => 1,
            'b' => [
                'c' => 2,
                'd' => 3,
            ],
            'e' => [
                'f' => [
                    'g' => 4,
                ],
            ],
        ];

        $expected = [
            'a' => 1,
            'b.c' => 2,
            'b.d' => 3,
            'e.f.g' => 4,
        ];

        $this->assertEquals($expected, array_flatten_prefixed($array));
    }

    public function testArrayShiftPluckInt(): void
    {
        $array = [
            [
                'id' => 1,
                'name' => 'test',
            ],
            [
                'id' => 2,
                'name' => 'test2',
            ],
        ];

        $this->assertEquals(1, array_shift_pluck_int($array, 'id'));
    }

    public function testArrayShiftPluckIntReturnsNullForEmptyArray(): void
    {
        $this->assertNull(array_shift_pluck_int([], 'id'));
    }

    public function testArrayShiftPluckIntReturnsNullForNonNumericValue(): void
    {
        $array = [['id' => 'abc']];
        $this->assertNull(array_shift_pluck_int($array, 'id'));
    }

    public function testArrayFirst(): void
    {
        $array = [
            'a',
            'b',
            'c',
        ];
        $this->assertEquals('a', array_first($array));
    }

    public function testArrayFirstReturnsNullForEmptyArray(): void
    {
        $this->assertNull(array_first([]));
    }

    public function testArrayUnshiftKey(): void
    {
        $array = [
            'a' => [
                1,
                2,
            ],
        ];
        $result = array_unshift_key($array, 'a', 3);
        $this->assertEquals(
            [
                'a' => [
                    1,
                    2,
                    3,
                ],
            ],
            $result
        );

        $array = [];
        $result = array_unshift_key($array, 'a', 1);
        $this->assertEquals(['a' => [1]], $result);
    }
}
