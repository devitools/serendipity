<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL\Pdo;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\PdoConnectionChecker;
use Serendipity\Infrastructure\Database\Relational\Connection;
use Serendipity\Infrastructure\Database\Relational\ConnectionFactory;

class PdoConnectionCheckerTest extends TestCase
{
    public function testSuccessfulProbeUsesRequestedNamedConnectionOnce(): void
    {
        $database = $this->createMock(Connection::class);
        $database->expects($this->once())->method('query')->with('SELECT 1')->willReturn([['value' => 1]]);
        $factory = $this->createMock(ConnectionFactory::class);
        $factory->expects($this->once())->method('make')->with('health')->willReturn($database);
        $checker = new PdoConnectionChecker($factory, connection: 'health');

        $this->assertSame(1, $checker->check());
    }

    public function testFailedProbeDoesNotRetryOrDelayDespiteLegacySettings(): void
    {
        $database = $this->createMock(Connection::class);
        $database->expects($this->once())->method('query')->willThrowException(new RuntimeException('probe failure'));
        $factory = $this->createMock(ConnectionFactory::class);
        $factory->expects($this->once())->method('make')->with('default')->willReturn($database);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug')->with('Attempt to check server info failed', [
            'attempts' => 1,
            'microseconds' => 0,
            'max' => 1,
        ]);
        $checker = new PdoConnectionChecker($factory, $logger);

        $this->assertSame(1, $checker->check(100, 100000000));
    }

    public function testFactoryFailureReturnsUnavailableWithoutThrowing(): void
    {
        $factory = $this->createMock(ConnectionFactory::class);
        $factory->expects($this->once())->method('make')->willThrowException(new RuntimeException('factory failure'));
        $checker = new PdoConnectionChecker($factory);

        $this->assertFalse($checker->isAvailable());
    }
}
