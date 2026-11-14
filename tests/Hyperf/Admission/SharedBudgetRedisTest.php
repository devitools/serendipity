<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Admission;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Hyperf\Config\Config;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\RateLimit\Handler\RateLimitHandler;
use Hyperf\RateLimit\Storage\RedisStorage;
use Hyperf\Redis\Pool\PoolFactory;
use Hyperf\Redis\RedisFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;
use Serendipity\Hyperf\Admission\MonotonicClock;
use Serendipity\Hyperf\Admission\SharedBudget;
use Serendipity\Hyperf\Middleware\AdmissionMiddleware;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

final class SharedBudgetRedisTest extends TestCase
{
    private Container $container;

    private ConfigInterface $config;

    private array $redisConfig;

    private array $rateConfig;

    private RedisFactory $originalFactory;

    private string $pool;

    private string $key;

    protected function setUp(): void
    {
        $this->container = ApplicationContext::getContainer();
        $this->config = $this->container->get(ConfigInterface::class);
        $this->redisConfig = $this->config->get('redis');
        $this->rateConfig = $this->config->get('rate_limit', []);
        $this->originalFactory = $this->container->get(RedisFactory::class);
        $this->pool = 'admission_test_' . bin2hex(random_bytes(8));
        $this->key = $this->pool;
        $this->configurePool($this->redisConfig['admission_test']);
    }

    protected function tearDown(): void
    {
        try {
            $this->originalFactory->get('admission_test')->del(RedisStorage::KEY_PREFIX . $this->key);
            $this->container->get(PoolFactory::class)->getPool($this->pool)->flushAll();
        } finally {
            $this->config->set('redis', $this->redisConfig);
            $this->config->set('rate_limit', $this->rateConfig);
            $this->container->set(RedisFactory::class, $this->originalFactory);
        }
    }

    public function testIndependentInstancesShareTheSameNativeValkeyBudget(): void
    {
        $first = $this->budget();
        $second = $this->budget();

        $firstAllowed = $first->consume();
        $secondAllowed = $second->consume();

        $this->assertTrue($firstAllowed);
        $this->assertFalse($secondAllowed);
        $this->assertFalse($first->isDegraded());
        $this->assertFalse($second->isDegraded());
    }

    public function testRealLockContentionRefusesWithoutDegrading(): void
    {
        $storage = new RedisStorage($this->container, $this->key, 10, ['pool' => $this->pool]);
        $started = new Channel(1);
        $release = new Channel(1);
        $finished = new Channel(1);
        Coroutine::create(static function () use ($storage, $started, $release, $finished): void {
            $storage->getMutex()->synchronized(static function () use ($started, $release): void {
                $started->push(true);
                $release->pop(5);
            });
            $finished->push(true);
        });
        $this->assertTrue($started->pop(1));
        $budget = $this->budget();

        try {
            $allowed = $budget->consume();
        } finally {
            $release->push(true);
            $this->assertTrue($finished->pop(1));
        }

        $this->assertFalse($allowed);
        $this->assertFalse($budget->isDegraded());
        $this->assertTrue($budget->consume());
    }

    public function testUnavailableValkeyKeepsLocalConcurrencyProtection(): void
    {
        $this->configurePool(array_replace($this->redisConfig['admission_test'], ['host' => '127.0.0.1', 'port' => 1]));
        $budget = $this->budget();
        $middleware = new AdmissionMiddleware(
            new Config(['admission' => ['enabled' => true, 'concurrency' => 1]]),
            $budget,
            new NullLogger(),
        );
        $request = new ServerRequest('POST', 'http://localhost/auction');
        $started = new Channel(1);
        $release = new Channel(1);
        $finished = new Channel(1);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->exactly(2))->method('handle')->willReturnCallback(
            static function () use ($started, $release): ResponseInterface {
                $started->push(true);
                $release->pop(1);
                return new Response(204);
            },
        );
        Coroutine::create(static function () use ($middleware, $request, $handler, $finished): void {
            $finished->push($middleware->process($request, $handler));
        });
        $this->assertTrue($started->pop(2));
        $rejectedHandler = $this->createMock(RequestHandlerInterface::class);
        $rejectedHandler->expects($this->never())->method('handle');

        $refused = $middleware->process($request, $rejectedHandler);
        $release->push(true);
        $accepted = $finished->pop(1);
        $release->push(true);
        $next = $middleware->process($request, $handler);

        $this->assertSame(429, $refused->getStatusCode());
        $this->assertSame('1', $refused->getHeaderLine('Retry-After'));
        $this->assertTrue($budget->isDegraded());
        $this->assertSame(204, $accepted->getStatusCode());
        $this->assertSame('true', $accepted->getHeaderLine('Admission-Degraded'));
        $this->assertSame(204, $next->getStatusCode());
    }

    private function configurePool(array $settings): void
    {
        $this->config->set('redis', array_replace($this->redisConfig, [$this->pool => $settings]));
        $this->config->set('rate_limit', [
            'storage' => ['class' => RedisStorage::class, 'options' => ['pool' => $this->pool]],
        ]);
        $this->container->set(RedisFactory::class, new RedisFactory($this->config));
    }

    private function budget(): SharedBudget
    {
        return new SharedBudget(
            new RateLimitHandler($this->container),
            new Config(['admission' => ['key' => $this->key, 'rate' => 1, 'burst' => 1]]),
            new NullLogger(),
            new MonotonicClock(),
        );
    }
}
