<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Testing\Extension;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Testing\Mock\LoggerExtensionMock;
use Serendipity\Hyperf\Testing\Observability\Logger\InMemory\Memory;
use Serendipity\Testing\FailException;

final class LoggerExtensionTest extends TestCase
{
    private LoggerExtensionMock $mock;

    private bool $assertionCalled = false;

    protected function setUp(): void
    {
        parent::setUp();
        Memory::clear();

        $this->mock = new LoggerExtensionMock(
            function (mixed $value, Constraint $constraint, string $message = '') {
                $this->assertionCalled = true;
                self::assertThat($value, $constraint, $message);
            }
        );
    }

    protected function tearDown(): void
    {
        Memory::clear();
        parent::tearDown();
    }

    public function testSetUpLoggerShouldRegisterTearDown(): void
    {
        // Act
        $this->mock->exposeSetUpLogger();

        // Assert
        $this->assertTrue($this->mock->getIsLoggerSetup());
        $this->assertCount(1, $this->mock->getRegisteredTearDowns());
    }

    public function testTearDownLoggerShouldClearMemoryAndUpdateState(): void
    {
        // Arrange
        $this->mock->exposeSetUpLogger();
        Memory::write('info', 'teste', ['key' => 'value']);
        $this->assertCount(1, Memory::all());

        // Act
        $this->mock->exposeTearDownLogger();

        // Assert
        $this->assertFalse($this->mock->getIsLoggerSetup());
        $this->assertCount(0, Memory::all());
    }

    public function testAssertLoggedShouldFailWhenLoggerNotSetUp(): void
    {
        // Assert
        $this->expectException(FailException::class);
        $this->expectExceptionMessage('Request is not set up.');

        // Act
        $this->mock->exposeAssertLogged();
    }

    public function testAssertLoggedShouldPassWhenLogContainsMatch(): void
    {
        // Arrange
        $this->mock->exposeSetUpLogger();
        Memory::write('info', 'mensagem teste', ['key' => 'value']);
        Memory::write('error', 'outra mensagem', ['key' => 'value']);

        // Act
        $this->mock->exposeAssertLogged('/teste/', 'info');

        // Assert
        $this->assertTrue($this->assertionCalled);
    }

    public function testTallyShouldCountMatchingRecords(): void
    {
        // Arrange
        Memory::write('info', 'teste 1', ['key' => 'value']);
        Memory::write('info', 'teste 2', ['key' => 'value']);
        Memory::write('info', 'outro log', ['key' => 'value']);
        Memory::write('error', 'teste 3', ['key' => 'value']);

        // Act & Assert
        $this->assertEquals(2, $this->mock->exposeTally('/teste/', 'info'));
        $this->assertEquals(1, $this->mock->exposeTally('/teste/', 'error'));
        $this->assertEquals(0, $this->mock->exposeTally('/não existe/', 'info'));
        $this->assertEquals(3, $this->mock->exposeTally('/.*/', 'info'));
        $this->assertEquals(3, $this->mock->exposeTally('/.*te.*/', null));
        $this->assertEquals(4, $this->mock->exposeTally(null, null));
    }

    public function testAssertLoggedShouldMatchByPatternOnly(): void
    {
        // Arrange
        $this->mock->exposeSetUpLogger();
        Memory::write('info', 'mensagem teste', ['key' => 'value']);
        Memory::write('error', 'mensagem teste', ['key' => 'value']);

        // Act
        $this->mock->exposeAssertLogged('/teste/');

        // Assert
        $this->assertTrue($this->assertionCalled);
    }

    public function testAssertLoggedShouldMatchByLevelOnly(): void
    {
        // Arrange
        $this->mock->exposeSetUpLogger();
        Memory::write('info', 'mensagem um', ['key' => 'value']);
        Memory::write('error', 'mensagem dois', ['key' => 'value']);

        // Act
        $this->mock->exposeAssertLogged(null, 'error');

        // Assert
        $this->assertTrue($this->assertionCalled);
    }

    public function testAssertLoggedShouldMatchAnyLogWhenNoFiltersProvided(): void
    {
        // Arrange
        $this->mock->exposeSetUpLogger();
        Memory::write('info', 'mensagem', ['key' => 'value']);

        // Act
        $this->mock->exposeAssertLogged();

        // Assert
        $this->assertTrue($this->assertionCalled);
    }
}
