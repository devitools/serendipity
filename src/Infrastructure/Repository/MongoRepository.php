<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository;

use DateMalformedStringException;
use DateTime;
use DateTimeZone;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Override;
use Serendipity\Infrastructure\Database\Document\MongoFactory;
use Serendipity\Infrastructure\Database\Managed;

use function Constructo\Cast\mapify;

abstract class MongoRepository extends Repository
{
    protected readonly Collection $collection;

    protected bool $transform = true;

    public function __construct(
        protected readonly Managed $managed,
        MongoFactory $mongoFactory,
    ) {
        $this->collection = $mongoFactory->make($this->resource());
    }

    abstract protected function resource(): string;

    /**
     * @throws DateMalformedStringException
     */
    protected function toDateTime(string $datetime): UTCDateTime
    {
        $dateTime = new DateTime($datetime, new DateTimeZone('UTC'));
        return new UTCDateTime($dateTime->getTimestamp() * 1000);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function normalize(mixed $datum): array
    {
        return $this->transform
            ? $this->transform($datum)
            : parent::normalize($datum);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transform(mixed $datum): array
    {
        if ($datum instanceof BSONDocument || $datum instanceof BSONArray) {
            $datum = $datum->getArrayCopy();
        }
        if (is_array($datum)) {
            $this->transformRecursive($datum);
            return mapify($datum);
        }
        return parent::normalize($datum);
    }

    /**
     * @SuppressWarnings(CyclomaticComplexity)
     */
    private function transformRecursive(array &$array): void
    {
        foreach ($array as &$value) {
            if ($value instanceof BSONDocument || $value instanceof BSONArray) {
                $value = $value->getArrayCopy();
                $this->transformRecursive($value);
                continue;
            }
            if (is_array($value)) {
                $this->transformRecursive($value);
            }
        }
        unset($value);
    }
}
