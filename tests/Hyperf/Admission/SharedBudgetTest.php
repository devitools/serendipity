<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Admission;

use bandwidthThrottle\tokenBucket\Rate;
use bandwidthThrottle\tokenBucket\storage\SingleProcessStorage;
use bandwidthThrottle\tokenBucket\storage\StorageException;
use bandwidthThrottle\tokenBucket\TokenBucket;
use Hyperf\Config\Config;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Exception\SocketPopException;
use Hyperf\RateLimit\Handler\RateLimitHandler;
use InvalidArgumentException;
use malkusch\lock\exception\TimeoutException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RedisException;
use RuntimeException;
use Serendipity\Hyperf\Admission\MonotonicClock;
use Serendipity\Hyperf\Admission\SharedBudget;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

final class SharedBudgetTest extends TestCase
{
    private float $time = 1000.0;

    public function testUsesTheNativeBucketAndConsumesOnlyOneTokenPerAttempt(): void
    {
        $bucket = $this->bucket(4);
        $limiter = $this->createMock(RateLimitHandler::class);
        $limiter->expects($this->exactly(5))->method('build')->with('dev:integrations', 4, 4, 1)
            ->willReturn($bucket);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $budget = $this->budget($limiter, $logger);

        $results = [];
        for ($index = 0; $index < 5; ++$index) {
            $results[] = $budget->consume();
        }

        $this->assertSame([true, true, true, true, false], $results);
        $this->assertFalse($budget->isDegraded());
    }

    public function testRejectsInvalidConfigurationBeforeBuildingABucket(): void
    {
        foreach ([['key' => ''], ['rate' => 0], ['burst' => 0]] as $invalid) {
            $limiter = $this->createMock(RateLimitHandler::class);
            $limiter->expects($this->never())->method('build');
            $config = new Config(['admission' => array_replace(['key' => 'dev:integrations'], $invalid)]);
            $budget = new SharedBudget($limiter, $config, $this->createMock(LoggerInterface::class), $this->clock());

            try {
                $budget->consume();
                $this->fail('Invalid admission configuration was accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Admission requires a key and positive rate and burst.', $exception->getMessage());
            }
        }
    }

    public function testLockContentionRefusesWithoutDeclaringTheKvUnavailable(): void
    {
        $limiter = $this->createMock(RateLimitHandler::class);
        $limiter->expects($this->once())->method('build')->willThrowException(
            new StorageException('Could not lock bootstrapping', 0, new TimeoutException('Busy')),
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $budget = $this->budget($limiter, $logger);

        $allowed = $budget->consume();

        $this->assertFalse($allowed);
        $this->assertFalse($budget->isDegraded());
    }

    public function testTransportAndStorageFailuresUseLocalProtectionAndSafeDiagnostics(): void
    {
        foreach ([
            new RedisException('Sensitive connection detail'),
            new ConnectionException('Sensitive connection detail'),
            new SocketPopException('Sensitive connection detail'),
            new StorageException('Sensitive connection detail'),
        ] as $failure) {
            $limiter = $this->createMock(RateLimitHandler::class);
            $limiter->expects($this->once())->method('build')->willThrowException($failure);
            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects($this->once())->method('warning')->with(
                'Shared admission budget unavailable; using local concurrency protection',
                [
                    'component' => 'admission',
                    'degraded' => true,
                    'exception_class' => $failure::class,
                    'exception_code' => 0,
                ],
            );
            $budget = $this->budget($limiter, $logger);

            $allowed = $budget->consume();
            $duringCooldown = $budget->consume();

            $this->assertTrue($allowed);
            $this->assertTrue($duringCooldown);
            $this->assertTrue($budget->isDegraded());
        }
    }

    public function testProbesOnlyOnceAfterFiveSecondsAndRecovers(): void
    {
        $started = new Channel(1);
        $release = new Channel(1);
        $finished = new Channel(1);
        $calls = 0;
        $bucket = $this->bucket(4);
        $limiter = $this->createMock(RateLimitHandler::class);
        $limiter->expects($this->exactly(2))->method('build')->willReturnCallback(
            static function () use (&$calls, $started, $release, $bucket): TokenBucket {
                if (++$calls === 1) {
                    throw new RedisException('Offline');
                }
                $started->push(true);
                $release->pop(1);
                return $bucket;
            },
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->once())->method('info');
        $budget = $this->budget($limiter, $logger);
        $this->assertTrue($budget->consume());
        $this->time = 1004.0;
        $this->assertTrue($budget->consume());
        $this->time = 1005.0;

        Coroutine::create(static function () use ($budget, $finished): void {
            $finished->push($budget->consume());
        });
        $this->assertTrue($started->pop(1));
        $this->assertTrue($budget->consume());
        $this->assertTrue($budget->isDegraded());
        $release->push(true);

        $this->assertTrue($finished->pop(1));
        $this->assertFalse($budget->isDegraded());
        $this->assertSame(2, $calls);
    }

    public function testFailedProbeReleasesItsGuardAndSchedulesTheNextProbe(): void
    {
        $calls = 0;
        $bucket = $this->bucket(4);
        $limiter = $this->createMock(RateLimitHandler::class);
        $limiter->expects($this->exactly(3))->method('build')->willReturnCallback(
            static function () use (&$calls, $bucket): TokenBucket {
                if (++$calls < 3) {
                    throw new StorageException('Offline');
                }
                return $bucket;
            },
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->once())->method('info');
        $budget = $this->budget($limiter, $logger);
        $this->assertTrue($budget->consume());
        $this->time = 1005.0;
        $this->assertTrue($budget->consume());
        $this->time = 1009.0;
        $this->assertTrue($budget->consume());
        $this->assertSame(2, $calls);
        $this->time = 1010.0;

        $allowed = $budget->consume();

        $this->assertTrue($allowed);
        $this->assertFalse($budget->isDegraded());
    }

    public function testBusyRecoveryProbePreservesTheSharedLimitAndClearsDegradation(): void
    {
        $calls = 0;
        $limiter = $this->createMock(RateLimitHandler::class);
        $limiter->expects($this->exactly(2))->method('build')->willReturnCallback(
            static function () use (&$calls): TokenBucket {
                if (++$calls === 1) {
                    throw new RedisException('Offline');
                }
                throw new StorageException('Busy', 0, new TimeoutException('Busy'));
            },
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->once())->method('info');
        $budget = $this->budget($limiter, $logger);
        $this->assertTrue($budget->consume());
        $this->time = 1005.0;

        $allowed = $budget->consume();

        $this->assertFalse($allowed);
        $this->assertFalse($budget->isDegraded());
    }

    public function testOlderSuccessfulAdmissionCannotClearANewerCooldown(): void
    {
        $this->assertOlderAdmissionKeepsCooldown(null);
    }

    public function testOlderMutexTimeoutCannotClearANewerCooldown(): void
    {
        $this->assertOlderAdmissionKeepsCooldown(new StorageException('Busy', 0, new TimeoutException('Busy')));
    }

    public function testOlderAdmissionCannotCompleteOrReleaseTheCurrentProbe(): void
    {
        $olderStarted = new Channel(1);
        $olderRelease = new Channel(1);
        $olderFinished = new Channel(1);
        $probeStarted = new Channel(1);
        $probeRelease = new Channel(1);
        $probeFinished = new Channel(1);
        $calls = 0;
        $bucket = $this->bucket(4);
        $limiter = $this->createMock(RateLimitHandler::class);
        $limiter->expects($this->exactly(3))->method('build')->willReturnCallback(
            static function () use (&$calls, $olderStarted, $olderRelease, $probeStarted, $probeRelease, $bucket): TokenBucket {
                $attempt = ++$calls;
                if ($attempt === 1) {
                    $olderStarted->push(true);
                    $olderRelease->pop(1);
                    return $bucket;
                }
                if ($attempt === 2) {
                    throw new RedisException('Offline');
                }
                $probeStarted->push(true);
                $probeRelease->pop(1);
                return $bucket;
            },
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->once())->method('info');
        $budget = $this->budget($limiter, $logger);
        Coroutine::create(static function () use ($budget, $olderFinished): void {
            $olderFinished->push($budget->consume());
        });
        $this->assertTrue($olderStarted->pop(1));
        $this->assertTrue($budget->consume());
        $this->time = 1005.0;
        Coroutine::create(static function () use ($budget, $probeFinished): void {
            $probeFinished->push($budget->consume());
        });
        $this->assertTrue($probeStarted->pop(1));

        try {
            $olderRelease->push(true);
            $this->assertTrue($olderFinished->pop(1));
            $this->assertTrue($budget->isDegraded());
            $this->assertTrue($budget->consume());
            $this->assertSame(3, $calls);
        } finally {
            $probeRelease->push(true);
            $this->assertTrue($probeFinished->pop(1));
        }

        $this->assertFalse($budget->isDegraded());
    }

    public function testProbeCannotClearACooldownFromALaterFailure(): void
    {
        foreach ([null, new StorageException('Busy', 0, new TimeoutException('Busy'))] as $probeFailure) {
            $olderStarted = new Channel(1);
            $olderRelease = new Channel(1);
            $olderFinished = new Channel(1);
            $probeStarted = new Channel(1);
            $probeRelease = new Channel(1);
            $probeFinished = new Channel(1);
            $calls = 0;
            $bucket = $this->bucket(4);
            $limiter = $this->createMock(RateLimitHandler::class);
            $limiter->expects($this->exactly(4))->method('build')->willReturnCallback(
                static function () use (&$calls, $olderStarted, $olderRelease, $probeStarted, $probeRelease, $bucket, $probeFailure): TokenBucket {
                    $attempt = ++$calls;
                    if ($attempt === 1) {
                        $olderStarted->push(true);
                        $olderRelease->pop(1);
                        throw new RedisException('Later failure');
                    }
                    if ($attempt === 2) {
                        throw new RedisException('First failure');
                    }
                    if ($attempt === 3) {
                        $probeStarted->push(true);
                        $probeRelease->pop(1);
                        if ($probeFailure !== null) {
                            throw $probeFailure;
                        }
                    }
                    return $bucket;
                },
            );
            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects($this->once())->method('warning');
            $logger->expects($this->once())->method('info');
            $budget = $this->budget($limiter, $logger);
            $this->time = 1000.0;
            Coroutine::create(static function () use ($budget, $olderFinished): void {
                $olderFinished->push($budget->consume());
            });
            $this->assertTrue($olderStarted->pop(1));
            $this->assertTrue($budget->consume());
            $this->time = 1005.0;
            Coroutine::create(static function () use ($budget, $probeFinished): void {
                $probeFinished->push($budget->consume());
            });
            $this->assertTrue($probeStarted->pop(1));

            $olderRelease->push(true);
            $this->assertTrue($olderFinished->pop(1));
            $probeRelease->push(true);
            $this->assertSame($probeFailure === null, $probeFinished->pop(1));

            $this->assertTrue($budget->isDegraded());
            $this->time = 1009.0;
            $this->assertTrue($budget->consume());
            $this->assertSame(3, $calls);
            $this->time = 1010.0;
            $this->assertTrue($budget->consume());
            $this->assertFalse($budget->isDegraded());
        }
    }

    public function testUnexpectedErrorsPropagateWithoutOpeningTheCircuit(): void
    {
        $failure = new RuntimeException('Implementation error');
        $limiter = $this->createMock(RateLimitHandler::class);
        $limiter->expects($this->once())->method('build')->willThrowException($failure);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $budget = $this->budget($limiter, $logger);

        try {
            $budget->consume();
            $this->fail('Unexpected limiter error was hidden.');
        } catch (RuntimeException $actual) {
            $this->assertSame($failure, $actual);
            $this->assertFalse($budget->isDegraded());
        }
    }

    private function assertOlderAdmissionKeepsCooldown(?StorageException $olderFailure): void
    {
        $started = new Channel(1);
        $release = new Channel(1);
        $finished = new Channel(1);
        $calls = 0;
        $bucket = $this->bucket(4);
        $limiter = $this->createMock(RateLimitHandler::class);
        $limiter->expects($this->exactly(3))->method('build')->willReturnCallback(
            static function () use (&$calls, $started, $release, $bucket, $olderFailure): TokenBucket {
                $attempt = ++$calls;
                if ($attempt === 1) {
                    $started->push(true);
                    $release->pop(1);
                    if ($olderFailure !== null) {
                        throw $olderFailure;
                    }
                }
                if ($attempt === 2) {
                    throw new RedisException('Offline');
                }
                return $bucket;
            },
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $logger->expects($this->once())->method('info');
        $budget = $this->budget($limiter, $logger);
        Coroutine::create(static function () use ($budget, $finished): void {
            $finished->push($budget->consume());
        });
        $this->assertTrue($started->pop(1));
        $this->assertTrue($budget->consume());
        $this->assertTrue($budget->isDegraded());

        $release->push(true);
        $this->assertSame($olderFailure === null, $finished->pop(1));

        $this->assertTrue($budget->isDegraded());
        $this->time = 1004.0;
        $this->assertTrue($budget->consume());
        $this->assertSame(2, $calls);
        $this->time = 1005.0;
        $this->assertTrue($budget->consume());
        $this->assertFalse($budget->isDegraded());
    }

    private function budget(RateLimitHandler $limiter, LoggerInterface $logger): SharedBudget
    {
        return new SharedBudget(
            $limiter,
            new Config(['admission' => ['key' => 'dev:integrations']]),
            $logger,
            $this->clock(),
        );
    }

    private function clock(): MonotonicClock
    {
        $clock = $this->createMock(MonotonicClock::class);
        $clock->method('now')->willReturnCallback(fn (): float => $this->time);
        return $clock;
    }

    private function bucket(int $tokens): TokenBucket
    {
        $bucket = new TokenBucket(4, new Rate(4, Rate::SECOND), new SingleProcessStorage());
        $bucket->bootstrap($tokens);
        return $bucket;
    }
}
