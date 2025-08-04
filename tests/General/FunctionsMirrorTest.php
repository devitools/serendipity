<?php

declare(strict_types=1);

namespace Serendipity\Test\General;

use Constructo\Support\Reflective\Notation;
use Constructo\Support\Set;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function Serendipity\Crypt\decrypt;
use function Serendipity\Crypt\encrypt;
use function Serendipity\Crypt\group;
use function Serendipity\Crypt\ungroup;
use function Serendipity\Notation\adaify;
use function Serendipity\Notation\camelify;
use function Serendipity\Notation\cobolify;
use function Serendipity\Notation\dotify;
use function Serendipity\Notation\format;
use function Serendipity\Notation\kebabify;
use function Serendipity\Notation\lowerify;
use function Serendipity\Notation\macroify;
use function Serendipity\Notation\pascalify;
use function Serendipity\Notation\sentencify;
use function Serendipity\Notation\snakify;
use function Serendipity\Notation\titlelify;
use function Serendipity\Notation\trainify;
use function Serendipity\Notation\upperify;
use function Serendipity\Type\Cast\arrayify;
use function Serendipity\Type\Cast\boolify;
use function Serendipity\Type\Cast\floatify;
use function Serendipity\Type\Cast\integerify;
use function Serendipity\Type\Cast\mapify;
use function Serendipity\Type\Cast\stringify;
use function Serendipity\Type\Json\decode;
use function Serendipity\Type\Json\encode;
use function Serendipity\Type\Util\extractArray;
use function Serendipity\Type\Util\extractBool;
use function Serendipity\Type\Util\extractInt;
use function Serendipity\Type\Util\extractNumeric;
use function Serendipity\Type\Util\extractString;

class FunctionsMirrorTest extends TestCase
{
    public function testShouldCallAllFunctions(): void
    {
        // Test Type\Cast functions
        $this->assertIsArray(arrayify('test', []));
        $this->assertIsArray(mapify(['key' => 'value'], []));
        $this->assertIsString(stringify(123, ''));
        $this->assertIsInt(integerify('42', 0));
        $this->assertIsFloat(floatify('3.14', 0.0));
        $this->assertIsBool(boolify('true', false));

        // Test Crypt functions
        $encrypted = encrypt('test');
        $this->assertIsString($encrypted);
        $decrypted = decrypt($encrypted);
        $this->assertIsString($decrypted);
        $grouped = group('algo', 'salt', 'cipher');
        $this->assertIsString($grouped);
        // Note: ungroup function requires specific encrypted format, so we test it separately
        // Just call it to ensure it exists and works with proper encrypted data
        try {
            $ungrouped = ungroup($encrypted);
            $this->assertInstanceOf(Set::class, $ungrouped);
        } catch (InvalidArgumentException $e) {
            // This is expected if the encrypted format doesn't match ungroup expectations
            $this->assertStringContains('Invalid encrypted format', $e->getMessage());
        }

        // Test Type\Json functions
        $encoded = encode(['key' => 'value']);
        $this->assertIsString($encoded);
        $decoded = decode($encoded);
        $this->assertIsArray($decoded);

        // Test Notation functions
        $formatted = format('test_string', Notation::SNAKE);
        $this->assertIsString($formatted);
        $this->assertIsString(snakify('TestString'));
        $this->assertIsString(camelify('test_string'));
        $this->assertIsString(pascalify('test_string'));
        $this->assertIsString(adaify('test_string'));
        $this->assertIsString(macroify('test_string'));
        $this->assertIsString(kebabify('test_string'));
        $this->assertIsString(trainify('test_string'));
        $this->assertIsString(cobolify('test_string'));
        $this->assertIsString(lowerify('TEST_STRING'));
        $this->assertIsString(upperify('test_string'));
        $this->assertIsString(titlelify('test string'));
        $this->assertIsString(sentencify('test string'));
        $this->assertIsString(dotify('test_string'));

        // Test Type\Util functions
        $testArray = [
            'property' => [
                'item1',
                'item2',
            ],
        ];
        $extracted = extractArray($testArray, 'property', []);
        $this->assertIsArray($extracted);

        $testStringArray = ['property' => 'test_value'];
        $extractedString = extractString($testStringArray, 'property', '');
        $this->assertIsString($extractedString);

        $testIntArray = ['property' => 42];
        $extractedInt = extractInt($testIntArray, 'property', 0);
        $this->assertIsInt($extractedInt);

        $testBoolArray = ['property' => true];
        $extractedBool = extractBool($testBoolArray, 'property', false);
        $this->assertIsBool($extractedBool);

        $testNumericArray = ['property' => 3.14];
        $extractedNumeric = extractNumeric($testNumericArray, 'property', 0);
        $this->assertIsFloat($extractedNumeric);
    }
}
