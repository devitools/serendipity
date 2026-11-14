<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Hyperf\Config\Config;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Serendipity\Hyperf\Admission\SharedBudget;
use Serendipity\Hyperf\Middleware\AdmissionMiddleware;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

final class AdmissionMiddlewareTest extends TestCase
{
    public function testIsDisabledByDefaultAndLeavesTheResponseUnchanged(): void
    {
        $budget = $this->createMock(SharedBudget::class);
        $budget->expects($this->never())->method('consume');
        $middleware = new AdmissionMiddleware(new Config([]), $budget, $this->createMock(LoggerInterface::class));
        $expected = new Response(204);
        $handler = $this->handler($expected);

        $actual = $middleware->process($this->request(), $handler);

        $this->assertSame($expected, $actual);
    }

    public function testHealthAndMetricsBypassAdmission(): void
    {
        foreach (['/health', '/metrics'] as $path) {
            $budget = $this->createMock(SharedBudget::class);
            $budget->expects($this->never())->method('consume');
            $middleware = $this->middleware($budget);
            $expected = new Response(204);

            $actual = $middleware->process(new ServerRequest('GET', 'http://localhost' . $path), $this->handler($expected));

            $this->assertSame($expected, $actual);
        }
    }

    public function testInvalidConcurrencyRefusesBeforeTheBudgetOrBusinessHandler(): void
    {
        $budget = $this->createMock(SharedBudget::class);
        $budget->expects($this->never())->method('consume');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');
        $middleware = $this->middleware($budget, ['concurrency' => 0]);
        $this->expectException(InvalidArgumentException::class);

        $middleware->process($this->request(), $handler);
    }

    public function testRateRefusalLogsNoticeAndReturnsRetryAfterWithoutCallingTheBusinessHandler(): void
    {
        $budget = $this->createMock(SharedBudget::class);
        $budget->expects($this->exactly(2))->method('consume')->willReturnOnConsecutiveCalls(false, true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $logger->expects($this->once())->method('notice')->with('Request refused by admission', [
            'component' => 'admission',
            'http_status' => 429,
            'reason' => 'rate',
            'processed' => false,
        ]);
        $middleware = $this->middleware($budget, ['concurrency' => 1], $logger);
        $rejectedHandler = $this->createMock(RequestHandlerInterface::class);
        $rejectedHandler->expects($this->never())->method('handle');

        $refused = $middleware->process($this->request(), $rejectedHandler);
        $accepted = $middleware->process($this->request(), $this->handler(new Response(204)));

        $this->assertRefusal($refused);
        $this->assertSame(204, $accepted->getStatusCode());
    }

    public function testConcurrencyRefusalLogsNoticeAndReleasesAllFourSlots(): void
    {
        $started = new Channel(4);
        $release = new Channel(4);
        $finished = new Channel(4);
        $budget = $this->createMock(SharedBudget::class);
        $budget->expects($this->exactly(5))->method('consume')->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $logger->expects($this->once())->method('notice')->with('Request refused by admission', [
            'component' => 'admission',
            'http_status' => 429,
            'reason' => 'concurrency',
            'processed' => false,
        ]);
        $middleware = $this->middleware($budget, [], $logger);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->exactly(4))->method('handle')->willReturnCallback(
            static function () use ($started, $release): ResponseInterface {
                $started->push(true);
                $release->pop(1);
                return new Response(204);
            },
        );
        for ($index = 0; $index < 4; ++$index) {
            Coroutine::create(function () use ($middleware, $handler, $finished): void {
                $finished->push($middleware->process($this->request(), $handler)->getStatusCode());
            });
        }
        for ($index = 0; $index < 4; ++$index) {
            $this->assertTrue($started->pop(1));
        }
        $rejectedHandler = $this->createMock(RequestHandlerInterface::class);
        $rejectedHandler->expects($this->never())->method('handle');

        $refused = $middleware->process($this->request(), $rejectedHandler);
        $this->assertRefusal($refused);
        for ($index = 0; $index < 4; ++$index) {
            $release->push(true);
        }
        for ($index = 0; $index < 4; ++$index) {
            $this->assertSame(204, $finished->pop(1));
        }
        $next = $middleware->process($this->request(), $this->handler(new Response(204)));

        $this->assertSame(204, $next->getStatusCode());
    }

    public function testDoesNotReplayFailedBusinessOperationsAndPreservesTheirException(): void
    {
        $failure = new RuntimeException('server has gone away; another command is already in progress');
        $budget = $this->createMock(SharedBudget::class);
        $budget->expects($this->exactly(2))->method('consume')->willReturn(true);
        $middleware = $this->middleware($budget, ['concurrency' => 1]);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willThrowException($failure);

        try {
            $middleware->process($this->request(), $handler);
            $this->fail('Business failure was hidden.');
        } catch (RuntimeException $actual) {
            $this->assertSame($failure, $actual);
        }
        $next = $middleware->process($this->request(), $this->handler(new Response(204)));

        $this->assertSame(204, $next->getStatusCode());
    }

    public function testUnexpectedBudgetFailureAlsoReleasesTheLocalSlot(): void
    {
        $failure = new RuntimeException('Unexpected limiter failure');
        $calls = 0;
        $budget = $this->createMock(SharedBudget::class);
        $budget->expects($this->exactly(2))->method('consume')->willReturnCallback(
            static function () use (&$calls, $failure): bool {
                if (++$calls === 1) {
                    throw $failure;
                }
                return true;
            },
        );
        $middleware = $this->middleware($budget, ['concurrency' => 1]);
        $rejectedHandler = $this->createMock(RequestHandlerInterface::class);
        $rejectedHandler->expects($this->never())->method('handle');

        try {
            $middleware->process($this->request(), $rejectedHandler);
            $this->fail('Unexpected failure was hidden.');
        } catch (RuntimeException $actual) {
            $this->assertSame($failure, $actual);
        }
        $next = $middleware->process($this->request(), $this->handler(new Response(204)));

        $this->assertSame(204, $next->getStatusCode());
    }

    public function testSignalsDegradedAdmissionWithoutChangingSuccessfulBusinessResponses(): void
    {
        $budget = $this->createMock(SharedBudget::class);
        $budget->expects($this->once())->method('consume')->willReturn(true);
        $budget->method('isDegraded')->willReturn(true);
        $middleware = $this->middleware($budget);
        $expected = new Response(202, ['Existing-Header' => 'kept'], 'accepted');

        $actual = $middleware->process($this->request(), $this->handler($expected));

        $this->assertSame(202, $actual->getStatusCode());
        $this->assertSame('accepted', (string) $actual->getBody());
        $this->assertSame('kept', $actual->getHeaderLine('Existing-Header'));
        $this->assertSame('true', $actual->getHeaderLine('Admission-Degraded'));
    }

    private function middleware(SharedBudget $budget, array $settings = [], ?LoggerInterface $logger = null): AdmissionMiddleware
    {
        return new AdmissionMiddleware(
            new Config(['admission' => array_replace(['enabled' => true], $settings)]),
            $budget,
            $logger ?? $this->createMock(LoggerInterface::class),
        );
    }

    private function request(): ServerRequest
    {
        return new ServerRequest('POST', 'http://localhost/v1/integrations/auction/auto');
    }

    private function handler(ResponseInterface $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($response);
        return $handler;
    }

    private function assertRefusal(ResponseInterface $response): void
    {
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('1', $response->getHeaderLine('Retry-After'));
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(['code' => 'capacity_exceeded', 'processed' => false], json_decode((string) $response->getBody(), true));
    }
}
