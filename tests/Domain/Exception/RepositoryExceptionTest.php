<?php

declare(strict_types=1);

namespace Serendipity\Test\Domain\Exception;

use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Serendipity\Domain\Exception\RepositoryException;
use Serendipity\Domain\Exception\ThrowableType;

final class RepositoryExceptionTest extends TestCase
{
    public function testDefaultsWithoutPreviousException(): void
    {
        $exception = new RepositoryException('quotes');

        $this->assertSame('quotes', $exception->context);
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame(ThrowableType::UNTREATED, $exception->type);
    }

    public function testInheritsIntegerCodeAndMessageFromPreviousException(): void
    {
        $previous = new RuntimeException('database unavailable', 42);

        $exception = new RepositoryException('quotes', $previous);

        $this->assertSame('database unavailable', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testSqlstateStringRemainsAvailableThroughPreviousException(): void
    {
        $previous = new class extends PDOException {
            public function __construct()
            {
                parent::__construct('connection failure');
                $this->code = '08P01';
            }
        };

        $exception = new RepositoryException('quotes', $previous);

        $this->assertSame(0, $exception->getCode());
        $this->assertSame('connection failure', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame('08P01', $exception->getPrevious()->getCode());
    }

    public function testExplicitOverridesTakePrecedenceIncludingZeroCode(): void
    {
        $previous = new RuntimeException('original', 42);

        $exception = new RepositoryException(
            'quotes',
            $previous,
            ThrowableType::UNRECOVERABLE,
            'custom message',
            0,
        );

        $this->assertSame('custom message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame(ThrowableType::UNRECOVERABLE, $exception->type);
        $this->assertSame($previous, $exception->getPrevious());
    }
}
