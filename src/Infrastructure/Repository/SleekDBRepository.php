<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository;

use Serendipity\Infrastructure\Database\Document\SleekDBFactory;
use Serendipity\Infrastructure\Database\Managed;
use SleekDB\Store;

/**
 * @template T of object
 * @extends Repository<T>
 */
abstract class SleekDBRepository extends Repository
{
    protected readonly Store $store;

    public function __construct(
        protected readonly Managed $managed,
        SleekDBFactory $storeFactory,
    ) {
        $this->store = $storeFactory->make($this->resource());
    }

    abstract protected function resource(): string;
}
