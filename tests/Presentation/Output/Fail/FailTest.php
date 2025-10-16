<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\Output\Fail;

use Constructo\Testing\FakerExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Output\Fail\BadRequest;
use Serendipity\Presentation\Output\Fail\Conflict;
use Serendipity\Presentation\Output\Fail\ExpectationFailed;
use Serendipity\Presentation\Output\Fail\FailedDependency;
use Serendipity\Presentation\Output\Fail\Forbidden;
use Serendipity\Presentation\Output\Fail\Gone;
use Serendipity\Presentation\Output\Fail\LengthRequired;
use Serendipity\Presentation\Output\Fail\Locked;
use Serendipity\Presentation\Output\Fail\MethodNotAllowed;
use Serendipity\Presentation\Output\Fail\Misdirected;
use Serendipity\Presentation\Output\Fail\PayloadTooLarge;
use Serendipity\Presentation\Output\Fail\PaymentRequired;
use Serendipity\Presentation\Output\Fail\PreconditionFailed;
use Serendipity\Presentation\Output\Fail\PreconditionRequired;
use Serendipity\Presentation\Output\Fail\PropertiesAreTooLarge;
use Serendipity\Presentation\Output\Fail\ProxyAuthenticationRequired;
use Serendipity\Presentation\Output\Fail\RangeNotSatisfiable;
use Serendipity\Presentation\Output\Fail\RequestTimeout;
use Serendipity\Presentation\Output\Fail\TooEarly;
use Serendipity\Presentation\Output\Fail\TooMany;
use Serendipity\Presentation\Output\Fail\Unauthorized;
use Serendipity\Presentation\Output\Fail\UnavailableForLegalReasons;
use Serendipity\Presentation\Output\Fail\UnprocessableEntity;
use Serendipity\Presentation\Output\Fail\UnsupportedMediaType;
use Serendipity\Presentation\Output\Fail\UpdateRequired;

final class FailTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;

    #[DataProvider('failClassesProvider')]
    public function testFailClassesWithStringContent(string $className): void
    {
        $content = $this->generator()
            ->sentence();
        $properties = [
            'error_code' => $this->generator()
                ->numberBetween(400, 499),
        ];

        $instance = $className::createFrom($content, $properties);

        $this->assertEquals($content, $instance->content());
        $this->assertEquals(
            $properties,
            $instance->properties()
                ->toArray()
        );
        $this->assertInstanceOf($className, $instance);
    }

    #[DataProvider('failClassesProvider')]
    public function testFailClassesWithArrayContent(string $className): void
    {
        $content = [
            'errors' => [
                [
                    'field' => 'email',
                    'message' => 'Invalid email format',
                ],
                [
                    'field' => 'password',
                    'message' => 'Too short',
                ],
            ],
        ];

        $instance = $className::createFrom($content);

        $this->assertEquals($content, $instance->content());
        $this->assertEquals(
            [],
            $instance->properties()
                ->toArray()
        );
        $this->assertInstanceOf($className, $instance);
    }

    #[DataProvider('failClassesProvider')]
    public function testFailClassesWithIntegerContent(string $className): void
    {
        $content = $this->generator()
            ->numberBetween(1000, 9999);

        $instance = $className::createFrom($content);

        $this->assertEquals($content, $instance->content());
        $this->assertEquals(
            [],
            $instance->properties()
                ->toArray()
        );
        $this->assertInstanceOf($className, $instance);
    }

    #[DataProvider('failClassesProvider')]
    public function testFailClassesWithNullContent(string $className): void
    {
        $properties = [
            'reason' => 'Authentication failed',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $instance = $className::createFrom(null, $properties);

        $this->assertNull($instance->content());
        $this->assertEquals(
            $properties,
            $instance->properties()
                ->toArray()
        );
        $this->assertInstanceOf($className, $instance);
    }

    #[DataProvider('failClassesProvider')]
    public function testFailClassesWithProperties(string $className): void
    {
        $content = 'Validation failed';
        $properties = [
            'errors' => [
                'field1' => ['message' => 'Required'],
                'field2' => ['message' => 'Invalid format'],
            ],
            'request_id' => $this->generator()
                ->uuid(),
        ];

        $instance = $className::createFrom($content, $properties);

        $this->assertEquals($content, $instance->content());
        $this->assertEquals(
            $properties,
            $instance->properties()
                ->toArray()
        );
        $this->assertInstanceOf($className, $instance);
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function failClassesProvider(): array
    {
        return [
            'BadRequest' => [BadRequest::class],
            'Conflict' => [Conflict::class],
            'ExpectationFailed' => [ExpectationFailed::class],
            'FailedDependency' => [FailedDependency::class],
            'Forbidden' => [Forbidden::class],
            'Gone' => [Gone::class],
            'LengthRequired' => [LengthRequired::class],
            'Locked' => [Locked::class],
            'MethodNotAllowed' => [MethodNotAllowed::class],
            'Misdirected' => [Misdirected::class],
            'PayloadTooLarge' => [PayloadTooLarge::class],
            'PaymentRequired' => [PaymentRequired::class],
            'PreconditionFailed' => [PreconditionFailed::class],
            'PreconditionRequired' => [PreconditionRequired::class],
            'ProxyAuthenticationRequired' => [ProxyAuthenticationRequired::class],
            'RangeNotSatisfiable' => [RangeNotSatisfiable::class],
            'PropertiesAreTooLarge' => [PropertiesAreTooLarge::class],
            'RequestTimeout' => [RequestTimeout::class],
            'TooEarly' => [TooEarly::class],
            'TooMany' => [TooMany::class],
            'Unauthorized' => [Unauthorized::class],
            'UnavailableForLegalReasons' => [UnavailableForLegalReasons::class],
            'UnprocessableEntity' => [UnprocessableEntity::class],
            'UnsupportedMediaType' => [UnsupportedMediaType::class],
            'UpdateRequired' => [UpdateRequired::class],
        ];
    }
}
