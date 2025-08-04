<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Formatter;

use Constructo\Contract\Formatter;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeInterface;
use MongoDB\BSON\UTCDateTime;

class MongoDateTimeToEntity implements Formatter
{
    /**
     * @throws DateMalformedStringException
     */
    public function format(mixed $value, mixed $option = null): ?DateTimeInterface
    {
        return match (true) {
            $value instanceof DateTimeInterface => $value,
            $value instanceof UTCDateTime => match ($option) {
                DateTimeImmutable::class => new DateTimeImmutable(
                    $value->toDateTime()->format(DateTimeInterface::ATOM)
                ),
                default => $value->toDateTime()
            },
            is_string($value) => new DateTimeImmutable($value),
            default => null
        };
    }
}
