<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Formatter;

use Constructo\Contract\Formatter;
use Constructo\Type\Timestamp;
use DateMalformedStringException;
use DateTimeZone;
use MongoDB\BSON\UTCDateTime;

class MongoTimestampToDatabase implements Formatter
{
    /**
     * @throws DateMalformedStringException
     */
    public function format(mixed $value, mixed $option = null): ?UTCDateTime
    {
        if ($value instanceof Timestamp) {
            return new UTCDateTime($value->getTimestamp() * 1000);
        }
        if (is_string($value)) {
            $dateTime = new Timestamp($value, new DateTimeZone('UTC'));
            return new UTCDateTime($dateTime->getTimestamp() * 1000);
        }
        return null;
    }
}
