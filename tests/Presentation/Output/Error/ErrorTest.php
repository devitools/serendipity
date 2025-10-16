<?php

declare(strict_types=1);

namespace Serendipity\Test\Presentation\Output\Error;

use Constructo\Testing\FakerExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Testing\Extension\MakeExtension;
use Serendipity\Presentation\Output\Error\BadGateway;
use Serendipity\Presentation\Output\Error\GatewayTimeout;
use Serendipity\Presentation\Output\Error\InsufficientStorage;
use Serendipity\Presentation\Output\Error\InternalServerError;
use Serendipity\Presentation\Output\Error\LoopDetected;
use Serendipity\Presentation\Output\Error\NetworkAuthenticationRequired;
use Serendipity\Presentation\Output\Error\NotImplemented;
use Serendipity\Presentation\Output\Error\ProtocolVersionNotSupported;
use Serendipity\Presentation\Output\Error\ServiceUnavailable;
use Serendipity\Presentation\Output\Error\VariantAlsoNegotiates;

final class ErrorTest extends TestCase
{
    use MakeExtension;
    use FakerExtension;

    #[DataProvider('errorClassesProvider')]
    public function testErrorClassesWithStringContent(string $className): void
    {
        $content = 'Um erro interno ocorreu no servidor';
        $properties = [
            'trace_id' => $this->generator()
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

    #[DataProvider('errorClassesProvider')]
    public function testErrorClassesWithIntegerContent(string $className): void
    {
        $content = $this->generator()
            ->numberBetween(500, 599);

        $instance = $className::createFrom($content);

        $this->assertEquals($content, $instance->content());
        $this->assertEquals(
            [],
            $instance->properties()
                ->toArray()
        );
        $this->assertInstanceOf($className, $instance);
    }

    #[DataProvider('errorClassesProvider')]
    public function testErrorClassesWithNullContent(string $className): void
    {
        $properties = [
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => 'api-server-01',
            'request_id' => $this->generator()
                ->uuid(),
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

    #[DataProvider('errorClassesProvider')]
    public function testErrorClassesWithDetailedProperties(string $className): void
    {
        $content = 'Sistema temporariamente indisponível';
        $properties = [
            'trace_id' => $this->generator()
                ->uuid(),
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => [
                'file' => 'PaymentProcessor.php',
                'line' => 423,
                'context' => [
                    'user_id' => 12345,
                    'action' => 'process_payment',
                ],
            ],
            'retry_after' => 300,
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
    public static function errorClassesProvider(): array
    {
        return [
            'InternalServerError' => [InternalServerError::class],
            'BadGateway' => [BadGateway::class],
            'InsufficientStorage' => [InsufficientStorage::class],
            'ServiceUnavailable' => [ServiceUnavailable::class],
            'ProtocolVersionNotSupported' => [ProtocolVersionNotSupported::class],
            'VariantAlsoNegotiates' => [VariantAlsoNegotiates::class],
            'NotImplemented' => [NotImplemented::class],
            'GatewayTimeout' => [GatewayTimeout::class],
            'NetworkAuthenticationRequired' => [NetworkAuthenticationRequired::class],
            'LoopDetected' => [LoopDetected::class],
        ];
    }
}
