<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository\Formatter;

use Constructo\Contract\Formatter;
use DateMalformedStringException;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use MongoDB\BSON\UTCDateTime;

class MongoDateTimeToDatabase implements Formatter
{
    /**
     * @throws DateMalformedStringException
     */
    public function format(mixed $value, mixed $option = null): ?UTCDateTime
    {
        if ($value instanceof DateTimeInterface) {
            return new UTCDateTime($value->getTimestamp() * 1000);
        }
        if (is_string($value)) {
            $dateTime = new DateTime($value, new DateTimeZone('UTC'));
            return new UTCDateTime($dateTime->getTimestamp() * 1000);
        }
        return null;
    }
}
