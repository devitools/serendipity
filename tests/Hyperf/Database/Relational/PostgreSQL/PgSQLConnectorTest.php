<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

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
use Swoole\Coroutine\PostgreSQL;

class PgSQLConnectorTest extends TestCase
{
    public function testConnectionAndSchemaAreInitializedBeforeClientIsReturned(): void
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class)->get('db.postgres');
        $config['schema'] = 'public';
        $session = $this->session();
        $connector = new PgSQLConnector();

        $driver = $connector->connect($config, $session);
        $rows = $driver->query("SELECT current_setting('search_path') AS schema")->fetchAll();

        $this->assertSame([['schema' => 'public']], $rows);
        $this->assertFalse($session->isValid());
    }

    public function testFailedConnectReturnsDiagnosticFailureWithoutRetry(): void
    {
        $driver = $this->driver(null);
        $driver->expects($this->once())->method('connect')->with(
            'host=db port=5432 dbname=app user=user password=secret',
        )->willReturn(false);
        $driver->expects($this->never())->method('query');
        $connector = $this->connector($driver);
        $session = $this->session();
        try {
            $connector->connect($this->config(), $session);
            $this->fail('Expected connection failure');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('PostgreSQL connect failed', $exception->getMessage());
            $this->assertStringContainsString('The driver returned no error message', $exception->getMessage());
        }
        $this->assertFalse($session->isValid());
    }

    public function testFailedSchemaDoesNotPublishUsableSessionOrRetry(): void
    {
        $driver = $this->driver();
        $driver->expects($this->once())->method('connect')->willReturn(true);
        $driver->expects($this->once())->method('query')->with('set search_path to public')->willReturn(false);
        $connector = $this->connector($driver);
        $session = $this->session();
        try {
            $connector->connect($this->config(), $session);
            $this->fail('Expected schema failure');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('PostgreSQL schema failed', $exception->getMessage());
        }
        $this->assertFalse($session->isValid());
    }

    public function testNativeConnectionExceptionIsPreservedWithoutAnotherAttempt(): void
    {
        $failure = new RuntimeException('server closed the connection unexpectedly');
        $driver = $this->driver();
        $driver->expects($this->once())->method('connect')->willThrowException($failure);
        $connector = $this->connector($driver);
        $session = $this->session();
        try {
            $connector->connect($this->config(), $session);
            $this->fail('Expected original native exception');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertFalse($session->isValid());
    }

    private function connector(PostgreSQL $driver): PgSQLConnector
    {
        $connector = $this->getMockBuilder(PgSQLConnector::class)->onlyMethods(['create'])->getMock();
        $connector->expects($this->once())->method('create')->willReturn($driver);
        return $connector;
    }

    private function session(): PgSQLSession
    {
        return new PgSQLSession(new PgSQLFailureClassifier(), new PgSQLDiagnostics(new NullLogger()), 'test');
    }

    private function config(): array
    {
        return ['host' => 'db', 'port' => 5432, 'database' => 'app', 'username' => 'user', 'password' => 'secret', 'schema' => 'public'];
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
}
