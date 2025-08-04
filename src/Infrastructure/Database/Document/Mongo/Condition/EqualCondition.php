<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Database\Document\Mongo\Condition;

use DateMalformedStringException;
use DateTime;
use MongoDB\BSON\UTCDateTime;
use Serendipity\Infrastructure\Database\Document\Mongo\Condition;

class EqualCondition implements Condition
{
    public function compose(string $value): array
    {
        $value = trim($value);
        $isDate = $this->isDate($value);
        return [
            '$eq' => $isDate
                ? $this->convertToDate($value)
                : $value,
        ];
    }

    private function isDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $value);
    }

    /**
     * @throws DateMalformedStringException
     */
    private function convertToDate(string $value): UTCDateTime
    {
        $dateTime = new DateTime($value);
        return new UTCDateTime($dateTime->getTimestamp() * 1000);
    }
}
