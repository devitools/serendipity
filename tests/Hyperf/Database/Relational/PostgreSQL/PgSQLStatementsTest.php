<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use Closure;
use ErrorException;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DB\Exception\QueryException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLConnector;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLDiagnostics;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailureClassifier;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLSession;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLStatements;
use Swoole\Coroutine\PostgreSQL;
use Swoole\Coroutine\PostgreSQLStatement;

use function sprintf;

class PgSQLStatementsTest extends TestCase
{
    public function testRealQueryAndExecutePreserveBindingsRowsAndAffectedCount(): void
    {
        $statements = new PgSQLStatements();
        $session = $this->session();
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class)->get('db.postgres');
        $driver = (new PgSQLConnector())->connect($config, $session);
        $statements->execute($session, $driver, 'CREATE TEMP TABLE statement_results (id integer)', []);

        $this->assertSame(1, $statements->execute($session, $driver, 'INSERT INTO statement_results VALUES (?)', [42]));
        $this->assertSame([['id' => 42]], $statements->query($session, $driver, 'SELECT id FROM statement_results WHERE id = ?', [42]));
        $this->assertTrue($session->isValid());
    }

    public function testMissingPrepareMessageRetiresSessionAndDoesNotRepeatPreparation(): void
    {
        foreach ([1, 7] as $status) {
            $statements = new PgSQLStatements();
            $session = $this->session();
            $driver = $this->driver(null);
            $driver->resultStatus = $status;
            $driver->resultDiag = null;
            $driver->expects($this->once())->method('prepare')->with('SELECT $1::text')->willReturn(false);
            try {
                $statements->query($session, $driver, 'SELECT ?::text', ['private binding']);
                $this->fail('Expected failed preparation');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('SQLSTATE unknown, error code 0, result status ' . $status, $exception->getMessage());
                $this->assertStringContainsString('The driver returned no error message', $exception->getMessage());
            }
            $this->assertFalse($session->isValid());
            try {
                $statements->query($session, $driver, 'SELECT 1', []);
                $this->fail('Expected unusable session guard');
            } catch (QueryException $exception) {
                $this->assertStringContainsString('not executed', $exception->getMessage());
            }
        }
    }

    public function testStructuredPrepareFailurePreservesOriginalMessageAndHealthySession(): void
    {
        $statements = new PgSQLStatements();
        $session = $this->session();
        $driver = $this->driver('syntax error', '42601');
        $driver->expects($this->once())->method('prepare')->willReturn(false);
        try {
            $statements->query($session, $driver, 'SELECT FROM', []);
            $this->fail('Expected SQL failure');
        } catch (QueryException $exception) {
            $this->assertSame('syntax error', $exception->getMessage());
        }
        $this->assertTrue($session->isValid());
    }

    public function testPreparationWarningPreservesExceptionAndInvalidatesSession(): void
    {
        $statements = new PgSQLStatements();
        $session = $this->session();
        $driver = $this->driver();
        $failure = new ErrorException('Swoole\Coroutine\PostgreSQL::prepare(): swoole_event_add failed');
        $driver->expects($this->once())->method('prepare')->willThrowException($failure);
        try {
            $statements->query($session, $driver, 'SELECT 1', []);
            $this->fail('Expected original native warning');
        } catch (ErrorException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertFalse($session->isValid());
    }

    public function testExecutionUsesStatementDiagnosticInsteadOfStaleClientError(): void
    {
        $statements = new PgSQLStatements();
        $session = $this->session(static fn (string $stage, Closure $next) => $stage === 'execute' ? false : $next());
        $driver = $this->driver('old connection error', '08006');
        $driver->expects($this->once())->method('prepare')->willReturn($this->statement('duplicate key', '23505'));
        try {
            $statements->execute($session, $driver, 'INSERT INTO example VALUES (?)', [1]);
            $this->fail('Expected constraint failure');
        } catch (QueryException $exception) {
            $this->assertSame('duplicate key', $exception->getMessage());
        }
        $this->assertTrue($session->isValid());
    }

    public function testFalseExecuteFetchAndAffectedRowsRetireSession(): void
    {
        foreach (['execute', 'fetch', 'affected_rows'] as $failedStage) {
            $statements = new PgSQLStatements();
            $driver = $this->driver('Coroutine timedout');
            $driver->expects($this->once())->method('prepare')->willReturn($this->statement());
            $session = $this->session(static function (string $stage, Closure $next) use ($failedStage): mixed {
                if ($stage === $failedStage) {
                    return false;
                }
                return $stage === 'execute' ? true : $next();
            });
            try {
                $failedStage === 'fetch'
                    ? $statements->query($session, $driver, 'SELECT 1', [])
                    : $statements->execute($session, $driver, 'UPDATE example SET id = 1', []);
                $this->fail('Expected native failure');
            } catch (QueryException) {
                $this->assertFalse($session->isValid());
            }
        }
    }

    public function testCleanupFailureCannotReplaceOriginalExecutionException(): void
    {
        $statements = new PgSQLStatements();
        $driver = $this->driver();
        $driver->expects($this->once())->method('prepare')->willReturn($this->statement());
        $primary = new ErrorException('swoole_event_add failed');
        $executions = 0;
        $session = $this->session(static function (string $stage, Closure $next) use ($primary, &$executions): mixed {
            if ($stage === 'execute') {
                ++$executions;
                throw $primary;
            }
            $result = $next();
            if ($stage === 'statement_cleanup') {
                throw new RuntimeException('cleanup failed');
            }
            return $result;
        });
        try {
            $statements->execute($session, $driver, 'INSERT INTO example VALUES (1)', []);
            $this->fail('Expected original execution failure');
        } catch (ErrorException $exception) {
            $this->assertSame($primary, $exception);
        }
        $this->assertSame(1, $executions);
        $this->assertFalse($session->isValid());
    }

    public function testCleanupFailureAfterSuccessPropagatesAndRetiresSession(): void
    {
        $statements = new PgSQLStatements();
        $driver = $this->driver();
        $driver->expects($this->once())->method('prepare')->willReturn($this->statement());
        $failure = new ErrorException('swoole_event_del failed');
        $session = $this->session(static function (string $stage, Closure $next) use ($failure): mixed {
            $result = $next();
            if ($stage === 'statement_cleanup') {
                throw $failure;
            }
            return $result;
        });
        try {
            $statements->query($session, $driver, 'SELECT 1', []);
            $this->fail('Expected cleanup warning');
        } catch (ErrorException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertFalse($session->isValid());
    }

    private function session(?Closure $interceptor = null): PgSQLSession
    {
        $arguments = [new PgSQLFailureClassifier(), new PgSQLDiagnostics(new NullLogger()), 'test'];
        if ($interceptor === null) {
            $session = new PgSQLSession(...$arguments);
        } else {
            $session = $this->getMockBuilder(PgSQLSession::class)->setConstructorArgs($arguments)->onlyMethods(['call'])->getMock();
            $session->method('call')->willReturnCallback(static fn ($stage, $driver, $fallback, $next) => $interceptor($stage, $next));
        }
        $session->activate();
        return $session;
    }

    private function driver(?string $error = '', string $sqlstate = ''): PostgreSQL
    {
        $driver = $this->createMock(PostgreSQL::class);
        $driver->error = $error;
        $driver->errCode = 0;
        $driver->resultStatus = 0;
        $driver->resultDiag = $sqlstate !== '' ? ['sqlstate' => $sqlstate] : [];
        return $driver;
    }

    private function statement(string $error = '', string $sqlstate = ''): PostgreSQLStatement
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $settings = $config->get('db.postgres');
        $native = new PostgreSQL();
        $native->connect(sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s',
            $settings['host'],
            $settings['port'],
            $settings['database'],
            $settings['username'],
            $settings['password']
        ));
        $statement = $native->prepare('SELECT 1');
        $statement->error = $error;
        $statement->errCode = 0;
        $statement->resultStatus = 0;
        $statement->resultDiag = $sqlstate !== '' ? ['sqlstate' => $sqlstate] : [];
        return $statement;
    }
}
