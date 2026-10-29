<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL;

use ErrorException;
use Hyperf\DB\Exception\QueryException;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailure;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\PgSQLFailureClassifier;
use Swoole\Coroutine\PostgreSQL;
use Swoole\Coroutine\PostgreSQLStatement;

class PgSQLFailureClassifierTest extends TestCase
{
    public function testClassifierDistinguishesSessionFailuresFromServerSqlErrors(): void
    {
        $classifier = new PgSQLFailureClassifier();
        foreach (['08006' => 'connection_lost', '57P01' => 'server_shutdown', '57P02' => 'server_shutdown', '57P03' => 'server_shutdown'] as $state => $reason) {
            $failure = new PgSQLFailure('', $state, '', 0, 7);
            $this->assertSame($reason, $classifier->reason($failure));
        }
        foreach (['42601', '23505', '25P02', '57014'] as $state) {
            $failure = new PgSQLFailure('server SQL error', $state, '', 0, 7);
            $this->assertNull($classifier->failureReason($failure));
        }
        foreach ([
            'Coroutine timedout' => 'client_timeout',
            'Coroutine canceled' => 'client_cancelled',
            'Coroutine cancelled' => 'client_cancelled',
            'swoole_event_add failed' => 'reactor_failure',
            'swoole_event_del failed' => 'reactor_failure',
            'Bad file descriptor' => 'reactor_failure',
            'another command is already in progress' => 'protocol_busy',
            'server closed the connection unexpectedly' => 'connection_lost',
            'no connection to the server' => 'connection_lost',
            'SSL connection has been closed unexpectedly' => 'connection_lost',
            'could not receive data from server' => 'connection_lost',
            'could not send data to server' => 'connection_lost',
            'reset by peer' => 'connection_lost',
            'Broken pipe' => 'connection_lost',
            'onerror' => 'connection_lost',
        ] as $message => $reason) {
            $this->assertSame($reason, $classifier->reason(new PgSQLFailure($message, '', '', 0, 0)));
        }
        $this->assertSame('driver_error', $classifier->reason(new PgSQLFailure('', '', '', 104, 0)));
        $this->assertSame('unknown_native_failure', $classifier->failureReason(new PgSQLFailure('', '', '', 0, 0)));
    }

    public function testServerSqlDiagnosticsTakePrecedenceOverTransportWords(): void
    {
        $classifier = new PgSQLFailureClassifier();
        foreach (['42P01', '23505', 'P0001', '57014'] as $state) {
            foreach (['onerror', 'broken pipe', 'Coroutine timedout', 'swoole_event_add failed'] as $message) {
                $failure = new PgSQLFailure('ERROR: ' . $message, $state, $message, 0, 7);

                $this->assertNull($classifier->failureReason($failure));
                $this->assertNull($classifier->reason($failure, new QueryException($message)));
                $this->assertNull($classifier->reason($failure, new ErrorException($message)));
                $this->assertNull($classifier->reason($failure, new QueryException('Swoole\Coroutine\PostgreSQL::query(): ' . $message)));
                $this->assertNull($classifier->reason($failure, new ErrorException('Swoole\Coroutine\PostgreSQL::query(): ERROR: ' . $message)));
            }
        }
    }

    public function testNativeFailuresOverrideStaleServerSqlDiagnostics(): void
    {
        $classifier = new PgSQLFailureClassifier();
        foreach ([
            'Coroutine timedout' => 'client_timeout',
            'Coroutine canceled' => 'client_cancelled',
            'Coroutine cancelled' => 'client_cancelled',
            'onerror' => 'connection_lost',
            'swoole_event_add failed' => 'reactor_failure',
            'swoole_event_del failed' => 'reactor_failure',
            'Bad file descriptor' => 'reactor_failure',
            'another command is already in progress' => 'protocol_busy',
            'server closed the connection unexpectedly' . "\n" . 'The server terminated abnormally' => 'connection_lost',
            'no connection to the server' => 'connection_lost',
            'SSL connection has been closed unexpectedly' => 'connection_lost',
            'could not receive data from server: Broken pipe' => 'connection_lost',
            'could not send data to server: Broken pipe' => 'connection_lost',
            'reset by peer' => 'connection_lost',
            'Broken pipe' => 'connection_lost',
        ] as $message => $reason) {
            $failure = new PgSQLFailure($message, '42P01', 'relation does not exist', 0, 7);

            $this->assertSame($reason, $classifier->failureReason($failure));
        }
        $this->assertSame('driver_error', $classifier->reason(new PgSQLFailure('', '42P01', '', 104, 7)));
        $this->assertSame('connection_lost', $classifier->reason(new PgSQLFailure('Broken pipe', '42P01', '', 32, 7)));
        foreach ([PostgreSQL::class, PostgreSQLStatement::class] as $driver) {
            $failure = new PgSQLFailure('ERROR: relation does not exist', '42P01', 'relation does not exist', 0, 7);
            $warning = new ErrorException($driver . '::query(): swoole_event_add failed');

            $this->assertSame('reactor_failure', $classifier->reason($failure, $warning));
        }
    }
}
