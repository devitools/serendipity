<?php

declare(strict_types=1);

namespace Serendipity\Domain\Exception;

use InvalidArgumentException;
use Serendipity\Domain\Exception\Adapter\NotResolved;
use Serendipity\Domain\Support\Set;
use Throwable;

use function array_map;
use function count;
use function implode;
use function sprintf;

final class AdapterException extends InvalidArgumentException
{
    /**
     * @param array<NotResolved> $unresolved
     */
    public function __construct(
        public readonly Set $values,
        private readonly array $unresolved = [],
        ?Throwable $error = null,
    ) {
        parent::__construct(
            message: $this->parse($unresolved, $error),
            previous: $error,
        );
    }

    public function getUnresolved(): array
    {
        return $this->unresolved;
    }

    /**
     * @param array<NotResolved> $notResolved
     */
    private function parse(array $notResolved, ?Throwable $error = null): string
    {
        if ($error !== null) {
            return $error->getMessage();
        }
        return sprintf(
            'Mapping failed with %d error(s). The errors are: "%s"',
            count($notResolved),
            implode('", "', $this->merge($notResolved)),
        );
    }

    /**
     * @param array<NotResolved> $errors
     * @return array|string[]
     */
    private function merge(array $errors): array
    {
        return array_map(fn (NotResolved $error) => $error->message(), $errors);
    }
}
