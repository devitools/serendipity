<?php

declare(strict_types=1);

namespace Serendipity\Test\Testing\Extension;

use Constructo\Support\Set;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;
use Serendipity\Domain\Contract\Testing\Helper;
use Serendipity\Testing\FailException;
use Serendipity\Testing\Mock\ResourceExtensionMock;

final class ResourceExtensionTest extends TestCase
{
    private ResourceExtensionMock $mock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock = new ResourceExtensionMock(
            function (mixed $value, Constraint $constraint, string $message = '') {
                self::assertThat($value, $constraint, $message);
            }
        );
    }

    public function testShouldSetUpResourceHelper(): void
    {
        $helper = $this->createMock(Helper::class);
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->assertSame($helper, $this->mock->getHelpers()['alias']);
    }

    public function testShouldSetUpResource(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->expects($this->once())
            ->method('truncate')
            ->with('resource');
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->mock->exposeSetUpResource('resource', 'alias');
        $this->assertInstanceOf(Helper::class, $this->mock->getResources()['resource']);
        $this->assertCount(1, $this->mock->getRegisteredTearDowns());
    }

    public function testShouldRaiseExceptionWithInvalidHelper(): void
    {
        $this->expectException(FailException::class);
        $this->expectExceptionMessage('Helper not defined');

        $this->mock->exposeSetUpResource('resource', 'alias');
    }

    public function testShouldSeed(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->expects($this->once())
            ->method('seed')
            ->with('type', 'resource', ['override'])
            ->willReturn(Set::createFrom([]));
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->mock->exposeSetUpResource('resource', 'alias');
        $this->mock->exposeSeed('type', ['override'], 'resource');
    }

    public function testShouldFailOnInvalidHelper(): void
    {
        $this->expectException(FailException::class);
        $this->expectExceptionMessage("Resource isn't defined");

        $this->mock->exposeSeed('type', ['override'], 'resource');
    }

    public function testShouldFailOnResourceNotDefined(): void
    {
        $this->expectException(FailException::class);
        $this->expectExceptionMessage("Resource isn't defined");

        $helper = $this->createMock(Helper::class);
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->mock->exposeSeed('type', ['override']);
    }

    public function testShouldSeedUsingTheUniqueResource(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->expects($this->once())
            ->method('seed')
            ->with('type', 'resource', ['override'])
            ->willReturn(Set::createFrom([]));
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->mock->exposeSetUpResource('resource', 'alias');
        $this->mock->exposeSeed('type', ['override']);
    }

    public function testShouldSeedResourceName(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->expects($this->once())
            ->method('seed')
            ->with('type', 'resource', ['override'])
            ->willReturn(Set::createFrom([]));
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->mock->exposeSetUpResource('resource', 'alias');
        $this->mock->exposeSeed('type', ['override'], 'resource');
    }

    public function testShouldAssertHas(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->expects($this->once())
            ->method('count')
            ->with('resource', ['filters'])
            ->willReturn(1);
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->mock->exposeSetUpResource('resource', 'alias');
        $this->mock->exposeAssertHas(['filters'], 'resource');
    }

    public function testShouldAssertHasNot(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->expects($this->once())
            ->method('count')
            ->with('resource', ['filters'])
            ->willReturn(0);
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->mock->exposeSetUpResource('resource', 'alias');
        $this->mock->exposeAssertHasNot(['filters'], 'resource');
    }

    public function testShouldAssertHasExactly(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->expects($this->once())
            ->method('count')
            ->with('resource', ['filters'])
            ->willReturn(1);
        $this->mock->exposeSetUpResourceHelper('alias', $helper);
        $this->mock->exposeSetUpResource('resource', 'alias');
        $this->mock->exposeAssertHasExactly(1, ['filters'], 'resource');
    }
}
