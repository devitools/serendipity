<?php

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational\PostgreSQL\Pdo;

use Hyperf\Database\Exception\QueryException;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Serendipity\Hyperf\Database\Relational\PostgreSQL\Pdo\SingleAttemptPostgreSQLConnection;

class SingleAttemptPostgreSQLConnectionTest extends TestCase
{
    public function testFailedReadAndWriteExecuteOnlyOnceAndNeverReconnect(): void
    {
        foreach (['select', 'insert', 'affectingStatement'] as $operation) {
            $pdo = $this->createMock(PDO::class);
            $statement = $this->createMock(PDOStatement::class);
            $failure = $this->failure('08006');
            $pdo->expects($this->once())->method('prepare')->willReturn($statement);
            $statement->expects($this->once())->method('execute')->willThrowException($failure);
            $database = new SingleAttemptPostgreSQLConnection($pdo);
            $database->setReconnector(function (): void {
                $this->fail('A failed operation must not reconnect or repeat');
            });

            try {
                $database->{$operation}('SELECT 1');
                $this->fail('Expected query failure');
            } catch (QueryException $exception) {
                $this->assertSame($failure, $exception->getPrevious());
            }
            $this->assertTrue($database->isInvalid());
        }
    }

    public function testBeginIsNotRepeatedAndInvalidSessionCannotContinue(): void
    {
        $pdo = $this->createMock(PDO::class);
        $failure = $this->failure('08006');
        $pdo->expects($this->once())->method('beginTransaction')->willThrowException($failure);
        $pdo->expects($this->never())->method('prepare');
        $database = new SingleAttemptPostgreSQLConnection($pdo);
        $database->setReconnector(function (): void {
            $this->fail('BEGIN must not reconnect');
        });
        try {
            $database->beginTransaction();
            $this->fail('Expected BEGIN failure');
        } catch (PDOException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('operation was not executed');
        $database->select('SELECT 1');
    }

    public function testCommitFailureIsPropagatedOnceAndRollbackIsOnlyLocal(): void
    {
        $pdo = $this->createMock(PDO::class);
        $failure = $this->failure('08006');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willThrowException($failure);
        $pdo->expects($this->never())->method('inTransaction');
        $pdo->expects($this->never())->method('rollBack');
        $database = new SingleAttemptPostgreSQLConnection($pdo);
        $database->beginTransaction();

        try {
            $database->commit();
            $this->fail('Expected uncertain COMMIT result');
        } catch (PDOException $exception) {
            $this->assertSame($failure, $exception);
        }
        $database->rollBack();
        $this->assertTrue($database->isInvalid());
        $this->assertSame(0, $database->transactionLevel());
    }

    public function testStructuredSqlErrorsDoNotInvalidateEvenWithTransportWords(): void
    {
        foreach (['42601', '23505', '57014', 'P0001'] as $state) {
            $database = new SingleAttemptPostgreSQLConnection($this->createMock(PDO::class));
            $failure = $this->failure($state);
            try {
                $database->withPdo(static fn () => throw $failure);
                $this->fail('Expected SQL failure');
            } catch (PDOException $exception) {
                $this->assertSame($failure, $exception);
            }
            $this->assertFalse($database->isInvalid());
        }
    }

    public function testFailedCommitResetsEndedTransactionWithoutReplayingCallback(): void
    {
        $pdo = $this->createMock(PDO::class);
        $failure = $this->failure('40001');
        $pdo->expects($this->exactly(2))->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willThrowException($failure);
        $pdo->expects($this->once())->method('inTransaction')->willReturn(false);
        $pdo->expects($this->never())->method('exec');
        $database = new SingleAttemptPostgreSQLConnection($pdo);
        $calls = 0;

        try {
            $database->transaction(static function () use (&$calls): void {
                ++$calls;
            });
            $this->fail('Expected COMMIT failure');
        } catch (PDOException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertSame(1, $calls);
        $this->assertFalse($database->isInvalid());
        $this->assertSame(0, $database->transactionLevel());
        $database->beginTransaction();
        $this->assertSame(1, $database->transactionLevel());
    }

    public function testFailedCommitPreservesStillOpenNativeTransaction(): void
    {
        $pdo = $this->createMock(PDO::class);
        $failure = $this->failure('P0001');
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willThrowException($failure);
        $pdo->method('inTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('rollBack')->willReturn(true);
        $database = new SingleAttemptPostgreSQLConnection($pdo);
        $database->beginTransaction();

        try {
            $database->commit();
            $this->fail('Expected COMMIT failure');
        } catch (PDOException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertSame(1, $database->transactionLevel());
        $database->rollBack();
        $this->assertSame(0, $database->transactionLevel());
    }

    public function testNestedBeginRecordsFailureAndCannotExecuteAnotherSavepoint(): void
    {
        $pdo = $this->createMock(PDO::class);
        $failure = $this->failure('08006');
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('exec')->with('SAVEPOINT trans2')->willThrowException($failure);
        $pdo->expects($this->never())->method('prepare');
        $database = new SingleAttemptPostgreSQLConnection($pdo);
        $database->beginTransaction();

        try {
            $database->beginTransaction();
            $this->fail('Expected SAVEPOINT failure');
        } catch (PDOException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertTrue($database->isInvalid());
        $this->assertSame(1, $database->transactionLevel());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('operation was not executed');
        $database->beginTransaction();
    }

    public function testNestedBeginCannotExecuteSavepointAfterEarlierConnectionFailure(): void
    {
        $pdo = $this->createMock(PDO::class);
        $failure = $this->failure('08006');
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->never())->method('exec');
        $database = new SingleAttemptPostgreSQLConnection($pdo);
        $database->beginTransaction();
        try {
            $database->withPdo(static fn () => throw $failure);
        } catch (PDOException) {
            $this->assertTrue($database->isInvalid());
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('operation was not executed');
        $database->beginTransaction();
    }

    public function testNativeTransactionStateDoesNotResolveLazyPdo(): void
    {
        $calls = 0;
        $database = new SingleAttemptPostgreSQLConnection(static function () use (&$calls): never {
            ++$calls;
            throw new RuntimeException('An untouched PDO must stay lazy');
        });

        $this->assertFalse($database->requiresDiscard());
        $this->assertSame(0, $calls);
    }

    public function testCommitDoesNotRepeatFailedLazyPdoInitialization(): void
    {
        $calls = 0;
        $failure = $this->failure('42601');
        $database = new SingleAttemptPostgreSQLConnection(static function () use (&$calls, $failure): never {
            ++$calls;
            throw $failure;
        });

        try {
            $database->commit();
            $this->fail('Expected initialization failure');
        } catch (PDOException $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertSame(1, $calls);
        $this->assertSame(0, $database->transactionLevel());
        $this->assertFalse($database->isInvalid());
    }

    public function testServerShutdownAndUnstructuredTransportErrorInvalidate(): void
    {
        foreach (['57P01', '57P02', '57P03', 'HY000', '0'] as $state) {
            $database = new SingleAttemptPostgreSQLConnection($this->createMock(PDO::class));
            try {
                $database->withPdo(fn () => throw $this->failure($state));
                $this->fail('Expected transport failure');
            } catch (PDOException) {
                $this->assertTrue($database->isInvalid());
            }
        }
    }

    public function testApplicationCallbackFailurePreservesSessionAndException(): void
    {
        $database = new SingleAttemptPostgreSQLConnection($this->createMock(PDO::class));
        $failure = new RuntimeException('server closed the connection unexpectedly');
        try {
            $database->withPdo(static fn () => throw $failure);
            $this->fail('Expected original application failure');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertFalse($database->isInvalid());
    }

    public function testTransactionHelperPreservesCallbackExceptionWhenCleanupFails(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('rollBack')->willThrowException($this->failure('08006'));
        $pdo->expects($this->never())->method('commit');
        $database = new SingleAttemptPostgreSQLConnection($pdo);
        $failure = new RuntimeException('initial failure');

        try {
            $database->transaction(static fn () => throw $failure);
            $this->fail('Expected original callback failure');
        } catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertTrue($database->isInvalid());
    }

    public function testTransactionHelperNeverReplaysCallbacksAndReturnsResult(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->once())->method('commit')->willReturn(true);
        $database = new SingleAttemptPostgreSQLConnection($pdo);
        $calls = 0;

        $this->assertSame(42, $database->transaction(static function () use (&$calls): int {
            ++$calls;
            return 42;
        }));
        $this->assertSame(1, $calls);
        $this->expectException(InvalidArgumentException::class);
        $database->transaction(static fn () => 0, 2);
    }

    private function failure(string $state): PDOException
    {
        $exception = new PDOException('server closed the connection unexpectedly');
        $exception->errorInfo = [$state, 7, $exception->getMessage()];
        return $exception;
    }
}
