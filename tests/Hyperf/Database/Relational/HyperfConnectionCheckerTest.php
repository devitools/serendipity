<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational;

use Hyperf\Pool\Exception\ConnectionException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Serendipity\Hyperf\Database\Relational\HyperfConnection;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionChecker;

class HyperfConnectionCheckerTest extends TestCase
{
    private HyperfConnection $database;

    private LoggerInterface $logger;

    private HyperfConnectionChecker $hyperfConnectionChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->database = $this->createMock(HyperfConnection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->hyperfConnectionChecker = new HyperfConnectionChecker($this->database, $this->logger);
    }

    final public function testCheckWithOneFailedAttempt(): void
    {
        $thrown = false;
        $this->database->method('run')
            ->willReturnCallback(function () use (&$thrown) {
                if ($thrown) {
                    return;
                }
                $thrown = true;
                throw new ConnectionException(
                    'Connection reconnect failed.:SQLSTATE[HY000] [2002] DNS Lookup resolve failed'
                );
            });

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Attempt to check server info failed', [
                'attempts' => 1,
                'microseconds' => 1000,
                'max' => 5,
            ]);

        $attempts = $this->hyperfConnectionChecker->check();
        $this->assertEquals(2, $attempts);
    }

    final public function testCheckWithAllFailedAttempts(): void
    {
        $this->database->method('run')
            ->willThrowException(
                new ConnectionException('Connection reconnect failed.:SQLSTATE[HY000] [2002] DNS Lookup resolve failed')
            );

        $this->logger->expects($this->exactly(5))
            ->method('debug')
            ->with(
                'Attempt to check server info failed',
                $this->callback(fn (array $parameters) => isset($parameters['attempts']))
            );

        $attempts = $this->hyperfConnectionChecker->check();
        $this->assertEquals(5, $attempts);
    }

    final public function testShouldCreateInstanceWithoutDebugging(): void
    {
        $thrown = false;
        $this->database->method('run')
            ->willReturnCallback(function () use (&$thrown) {
                if ($thrown) {
                    return;
                }
                $thrown = true;
                throw new ConnectionException(
                    'Connection reconnect failed.:SQLSTATE[HY000] [2002] DNS Lookup resolve failed'
                );
            });
        $instance = new HyperfConnectionChecker($this->database);
        $this->assertEquals(2, $instance->check());
    }
}
