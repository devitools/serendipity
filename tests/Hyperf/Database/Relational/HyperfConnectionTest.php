<?php

/** @noinspection SqlResolve */

declare(strict_types=1);

namespace Serendipity\Test\Hyperf\Database\Relational;

use Constructo\Support\Set;
use Hyperf\DB\DB as Database;
use PHPUnit\Framework\TestCase;
use Serendipity\Hyperf\Database\Relational\HyperfConnection;

final class HyperfConnectionTest extends TestCase
{
    private Database $database;

    private HyperfConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(Database::class);
        $this->connection = new HyperfConnection('test_connection', $this->database);
    }

    public function testBeginTransaction(): void
    {
        $this->database->expects($this->once())
            ->method('__call')
            ->with('beginTransaction', []);

        $this->connection->beginTransaction();
    }

    public function testCommit(): void
    {
        $this->database->expects($this->once())
            ->method('__call')
            ->with('commit', []);

        $this->connection->commit();
    }

    public function testRollback(): void
    {
        $this->database->expects($this->once())
            ->method('__call')
            ->with('rollback', []);

        $this->connection->rollback();
    }

    public function testInsert(): void
    {
        $query = 'INSERT INTO test (column) VALUES (?)';
        $bindings = ['value'];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'insert',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn(123);

        $result = $this->connection->insert($query, $bindings);
        $this->assertEquals(123, $result);
    }

    public function testInsertWithNonNumericReturn(): void
    {
        $query = 'INSERT INTO test (column) VALUES (?)';
        $bindings = ['value'];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'insert',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn('não numérico');

        $result = $this->connection->insert($query, $bindings);
        $this->assertEquals(0, $result);
    }

    public function testExecute(): void
    {
        $query = 'UPDATE test SET column = ? WHERE true';
        $bindings = ['value'];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'execute',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn(5);

        $result = $this->connection->execute($query, $bindings);
        $this->assertEquals(5, $result);
    }

    public function testQuery(): void
    {
        $query = 'SELECT * FROM test';
        $bindings = [];
        $expectedResult = [
            [
                'id' => 1,
                'name' => 'Test 1',
            ],
            [
                'id' => 2,
                'name' => 'Test 2',
            ],
        ];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'query',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn($expectedResult);

        $result = $this->connection->query($query, $bindings);
        $this->assertEquals($expectedResult, $result);
    }

    public function testQueryWithNonArrayReturn(): void
    {
        $query = 'SELECT * FROM test';
        $bindings = [];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'query',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn(null);

        $result = $this->connection->query($query, $bindings);
        $this->assertEquals([], $result);
    }

    public function testFetch(): void
    {
        $query = 'SELECT * FROM test WHERE id = ?';
        $bindings = [1];
        $expectedData = [
            'id' => 1,
            'name' => 'Test 1',
        ];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'fetch',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn((object) $expectedData);

        $result = $this->connection->fetch($query, $bindings);
        $this->assertEquals(Set::createFrom($expectedData), $result);
    }

    public function testFetchWithObjectResult(): void
    {
        $query = 'SELECT * FROM test WHERE id = ?';
        $bindings = [1];
        $expectedData = (object) [
            'id' => 1,
            'name' => 'Test 1',
        ];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'fetch',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn($expectedData);

        $result = $this->connection->fetch($query, $bindings);
        $this->assertEquals(Set::createFrom((array) $expectedData), $result);
    }

    public function testFetchWithArrayResult(): void
    {
        $query = 'SELECT * FROM test WHERE id = ?';
        $bindings = [1];
        $expectedData = [
            'id' => 1,
            'name' => 'Test 1',
        ];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'fetch',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn($expectedData);

        $result = $this->connection->fetch($query, $bindings);
        $this->assertEquals(Set::createFrom($expectedData), $result);
    }

    public function testFetchWithNullResult(): void
    {
        $query = 'SELECT * FROM test WHERE id = ?';
        $bindings = [999];

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'fetch',
                [
                    $query,
                    $bindings,
                ]
            )
            ->willReturn(null);

        $result = $this->connection->fetch($query, $bindings);
        $this->assertEquals(Set::createFrom([]), $result);
    }

    public function testRun(): void
    {
        $closure = fn () => true;

        $this->database->expects($this->once())
            ->method('__call')
            ->with(
                'run',
                $this->callback(fn (array $arg) => $arg[0] === $closure)
            );

        $this->connection->run($closure);
    }
}
