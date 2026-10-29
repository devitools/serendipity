<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailure;
use Swoole\Coroutine\PostgreSQL;
use Swoole\Coroutine\PostgreSQLStatement;

use function sprintf;

class PgSQLFailureTest extends TestCase
{
    public function testAbsentDriverProducesStableFallbackMessage(): void
    {
        $failure = PgSQLFailure::from(null);

        $this->assertTrue($failure->isEmpty());
        $this->assertSame(
            'PostgreSQL prepare failed (SQLSTATE unknown, error code 0, result status 0): The driver returned no error message',
            $failure->message('prepare'),
        );
    }

    public function testNativeErrorMessageIsPreserved(): void
    {
        $driver = $this->driver('original native error', '42601');
        $driver->errCode = 10;
        $driver->resultStatus = 7;
        $failure = PgSQLFailure::from($driver);

        $this->assertSame('original native error', $failure->message('execute'));
        $this->assertSame('42601', $failure->sqlstate);
        $this->assertSame(10, $failure->code);
        $this->assertSame(7, $failure->status);
    }

    public function testMissingMessageUsesStructuredDiagnostics(): void
    {
        $driver = $this->driver(null, '08006');
        $driver->resultDiag['message_primary'] = 'server closed the connection unexpectedly';
        $driver->resultStatus = 7;
        $failure = PgSQLFailure::from($driver);

        $this->assertSame(
            'PostgreSQL prepare failed (SQLSTATE 08006, error code 0, result status 7): server closed the connection unexpectedly',
            $failure->message('prepare'),
        );
    }

    public function testStatementDiagnosticsTakePrecedenceOverConnectionFields(): void
    {
        $statement = $this->statement('duplicate key', '23505');
        $driver = $this->driver('old connection error', '08006');
        $failure = PgSQLFailure::from($statement, $driver);

        $this->assertSame('duplicate key', $failure->error);
        $this->assertSame('23505', $failure->sqlstate);
    }

    public function testMissingStatementDiagnosticsUseConnectionSqlstate(): void
    {
        $statement = $this->statement('duplicate key');
        $statement->resultDiag = null;
        $driver = $this->driver('duplicate key', '23505');
        $failure = PgSQLFailure::from($statement, $driver);

        $this->assertSame('duplicate key', $failure->error);
        $this->assertSame('23505', $failure->sqlstate);
    }

    public function testEmptyStatementUsesConnectionFailure(): void
    {
        $statement = $this->statement();
        $driver = $this->driver('Coroutine timedout');
        $failure = PgSQLFailure::from($statement, $driver);

        $this->assertSame('Coroutine timedout', $failure->error);
        $this->assertFalse($failure->isEmpty());
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
