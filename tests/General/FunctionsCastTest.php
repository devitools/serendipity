<?php

declare(strict_types=1);

namespace Serendipity\Test\General;

use PHPUnit\Framework\TestCase;
use stdClass;

use function Serendipity\Type\Cast\arrayify;
use function Serendipity\Type\Cast\boolify;
use function Serendipity\Type\Cast\floatify;
use function Serendipity\Type\Cast\integerify;
use function Serendipity\Type\Cast\stringify;

final class FunctionsCastTest extends TestCase
{
    public function testToArrayReturnsArrayWhenValueIsArray(): void
    {
        $value = ['key' => 'value'];
        $result = arrayify($value);
        $this->assertEquals($value, $result);
    }

    public function testToArrayReturnsDefaultWhenValueIsNotArray(): void
    {
        $value = 'not an array';
        $default = ['default'];
        $result = arrayify($value, $default);
        $this->assertEquals($default, $result);
    }

    public function testToStringReturnsStringWhenValueIsString(): void
    {
        $value = 'string';
        $result = stringify($value);
        $this->assertEquals($value, $result);
    }

    public function testToStringReturnsDefaultWhenValueIsNotString(): void
    {
        $value = new stdClass();
        $default = 'default';
        $result = stringify($value, $default);
        $this->assertEquals($default, $result);
    }

    public function testToIntReturnsIntWhenValueIsInt(): void
    {
        $value = 123;
        $result = integerify($value);
        $this->assertEquals($value, $result);
    }

    public function testToIntReturnsDefaultWhenValueIsNotInt(): void
    {
        $value = 'not an int';
        $default = 456;
        $result = integerify($value, $default);
        $this->assertEquals($default, $result);
    }

    public function testToBoolReturnsBoolWhenValueIsBool(): void
    {
        $value = true;
        $result = boolify($value);
        $this->assertEquals($value, $result);
    }

    public function testToBoolReturnsDefaultWhenValueIsNotBool(): void
    {
        $value = 'not a bool';
        $default = true;
        $result = boolify($value, $default);
        $this->assertEquals(true, $result);
    }

    public function testFloatifyReturnsFloatWhenValueIsFloat(): void
    {
        $value = 123.45;
        $result = floatify($value);
        $this->assertEquals($value, $result);
    }

    public function testFloatifyReturnsDefaultWhenValueIsNotFloat(): void
    {
        $value = 'not a float';
        $default = 456.78;
        $result = floatify($value, $default);
        $this->assertEquals($default, $result);
    }

    public function testFloatifyConvertsNumericStringsToFloat(): void
    {
        $value = '123.45';
        $result = floatify($value);
        $this->assertEquals(123.45, $result);
    }

    public function testFloatifyConvertsIntegersToFloat(): void
    {
        $value = 123;
        $result = floatify($value);
        $this->assertEquals(123.0, $result);
        $this->assertIsFloat($result);
    }
}
