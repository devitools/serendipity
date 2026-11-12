<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Database\MySqlConnection;
use InvalidArgumentException;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoFailureClassifier;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptConnectionFactory;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptConnector;

class SingleAttemptConnectionFactoryTest extends TestCase
{
    public function testPostgresAlternativesAreRejectedBeforeResolvingAnyConnector(): void
    {
        $alternatives = [
            ['host' => ['primary', 'secondary']],
            ['host' => 'primary,secondary'],
            ['host' => ['primary,secondary']],
            ['host' => 'primary,'],
            ['read' => ['host' => ['primary', 'secondary']]],
            ['write' => ['host' => 'primary,secondary']],
            ['read' => [['host' => 'primary'], ['host' => 'secondary']]],
            ['write' => [['host' => 'primary'], ['host' => 'secondary']]],
        ];
        foreach ($alternatives as $override) {
            $factory = $this->getMockBuilder(SingleAttemptConnectionFactory::class)
                ->setConstructorArgs([$this->createMock(ContainerInterface::class)])
                ->onlyMethods(['createConnector'])->getMock();
            $factory->expects($this->never())->method('createConnector');
            $config = array_replace(['driver' => 'pgsql', 'database' => 'example', 'host' => 'single'], $override);

            try {
                $factory->make($config, 'example');
                $this->fail('Expected PostgreSQL alternatives to be rejected');
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('requires a single host', $exception->getMessage());
            }
        }
    }

    public function testSingleHostConnectAndInitializationFailuresExecuteOnce(): void
    {
        $hosts = [
            [],
            ['host' => 'single'],
            ['host' => ['single']],
            ['host' => 'single', 'read' => ['host' => 'reader'], 'write' => ['host' => ['writer']]],
            ['host' => 'single', 'read' => [['host' => 'reader']], 'write' => [['host' => 'writer']]],
        ];
        foreach (['08006', '42601'] as $state) {
            foreach ($hosts as $host) {
                $failure = new PDOException('connection or initialization failure');
                $failure->errorInfo = [$state, 7, $failure->getMessage()];
                $connector = $this->createMock(SingleAttemptConnector::class);
                $connector->expects($this->once())->method('connect')->willThrowException($failure);
                $container = $this->createMock(ContainerInterface::class);
                $container->method('get')->with(PdoFailureClassifier::class)->willReturn(new PdoFailureClassifier());
                $factory = $this->getMockBuilder(SingleAttemptConnectionFactory::class)
                    ->setConstructorArgs([$container])->onlyMethods(['createConnector'])->getMock();
                $factory->expects($this->once())->method('createConnector')->willReturn($connector);
                $database = $factory->make(array_replace(['driver' => 'pgsql', 'database' => 'example'], $host), 'example');

                try {
                    $database->withPdo(function (): never {
                        $this->fail('A failed connection must not execute the callback');
                    });
                    $this->fail('Expected initial connection failure');
                } catch (PDOException $exception) {
                    $this->assertSame($failure, $exception);
                }
                $this->assertSame($state === '08006', $database->isInvalid());
            }
        }
    }

    public function testOtherDriversKeepTheirHostAndReadWriteAlternatives(): void
    {
        $factory = new SingleAttemptConnectionFactory($this->createMock(ContainerInterface::class));
        $database = $factory->make([
            'driver' => 'mysql',
            'database' => 'example',
            'host' => ['primary', 'secondary'],
            'read' => [['host' => 'first'], ['host' => 'second']],
            'write' => ['host' => ['primary', 'secondary']],
        ]);

        $this->assertSame(MySqlConnection::class, $database::class);
    }
}
