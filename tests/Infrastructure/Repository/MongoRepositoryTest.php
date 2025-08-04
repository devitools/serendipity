<?php

declare(strict_types=1);

namespace Serendipity\Test\Infrastructure\Repository;

use PHPUnit\Framework\TestCase;
use Serendipity\Infrastructure\Database\Document\MongoFactory;
use Serendipity\Infrastructure\Database\Managed;

final class MongoRepositoryTest extends TestCase
{
    public function testResource(): void
    {
        $generator = $this->createMock(Managed::class);
        $mongoFactory = $this->createMock(MongoFactory::class);
        $mongoFactory->expects($this->once())
            ->method('make')
            ->with('x');
        new MongoRepositoryTestMock($generator, $mongoFactory);
    }
}
