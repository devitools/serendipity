<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation;

use Constructo\Testing\FakerExtension;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Test\Presentation\ReflectorInput\TestableReflectorInput;

final class ReflectorInputTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;

    public function testShouldTestFallbackMethodWithExistingField(): void
    {
        $input = new TestableReflectorInput();
        $data = ['existing_field' => 'value'];
        $result = $input->testFallback($data, 'existing_field');

        $this->assertEquals('existing_field', $result);
    }

    public function testShouldTestFallbackMethodWithNonExistingField(): void
    {
        $input = new TestableReflectorInput();
        $data = ['other_field' => 'value'];
        $result = $input->testFallback($data, 'non_existing_field');

        $this->assertNull($result);
    }

    public function testShouldTestFallbackMethodWithEmptyData(): void
    {
        $input = new TestableReflectorInput();
        $data = [];
        $result = $input->testFallback($data, 'any_field');

        $this->assertNull($result);
    }

    public function testShouldTestFallbackMethodWithNullValue(): void
    {
        $input = new TestableReflectorInput();
        $data = ['field_with_null' => null];
        $result = $input->testFallback($data, 'field_with_null');

        $this->assertNull($result);
    }

    public function testShouldTestFallbackMethodWithZeroValue(): void
    {
        $input = new TestableReflectorInput();
        $data = ['field_with_zero' => 0];
        $result = $input->testFallback($data, 'field_with_zero');

        $this->assertEquals('field_with_zero', $result);
    }

    public function testShouldTestFallbackMethodWithEmptyStringValue(): void
    {
        $input = new TestableReflectorInput();
        $data = ['field_with_empty_string' => ''];
        $result = $input->testFallback($data, 'field_with_empty_string');

        $this->assertEquals('field_with_empty_string', $result);
    }

    public function testShouldTestFallbackMethodWithFalseValue(): void
    {
        $input = new TestableReflectorInput();
        $data = ['field_with_false' => false];
        $result = $input->testFallback($data, 'field_with_false');

        $this->assertEquals('field_with_false', $result);
    }

    public function testShouldTestFallbackMethodWithArrayValue(): void
    {
        $input = new TestableReflectorInput();
        $data = ['field_with_array' => ['nested' => 'value']];
        $result = $input->testFallback($data, 'field_with_array');

        $this->assertEquals('field_with_array', $result);
    }

    public function testShouldTestFallbackMethodWithNumericKeys(): void
    {
        $input = new TestableReflectorInput();
        $data = [
            0 => 'first',
            1 => 'second',
            'string_key' => 'third',
        ];

        $this->assertEquals('0', $input->testFallback($data, '0'));
        $this->assertEquals('1', $input->testFallback($data, '1'));
        $this->assertEquals('string_key', $input->testFallback($data, 'string_key'));
        $this->assertNotNull($input->testFallback($data, '0'));
        $this->assertNull($input->testFallback($data, '2'));
    }

    public function testShouldTestFallbackMethodCaseSensitivity(): void
    {
        $input = new TestableReflectorInput();
        $data = [
            'CamelCase' => 'value',
            'lowercase' => 'value2',
        ];

        $this->assertEquals('CamelCase', $input->testFallback($data, 'CamelCase'));
        $this->assertNull($input->testFallback($data, 'camelcase'));
        $this->assertEquals('lowercase', $input->testFallback($data, 'lowercase'));
        $this->assertNull($input->testFallback($data, 'LOWERCASE'));
    }
}
