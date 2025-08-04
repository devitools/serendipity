<?php

declare(strict_types=1);

namespace Serendipity\Presentation\Input;

use function Hyperf\Collection\data_get;
use function Hyperf\Collection\data_set;
use function is_callable;
use function is_string;
use function Constructo\Cast\arrayify;

final class Mapped extends Resolver
{
    public function resolve(array $data): array
    {
        $mappings = $this->input->mappings();
        $payload = $data;
        foreach ($mappings as $target => $from) {
            $value = $this->extractValue(arrayify($data), $target, $from);
            if ($value === null) {
                continue;
            }
            data_set($payload, $target, $value);
        }
        return parent::resolve(arrayify($payload));
    }

    private function extractValue(array $data, int|string $target, mixed $from): mixed
    {
        return match (true) {
            is_string($from) => data_get($data, $from),
            is_callable($from) => $from($data, data_get($data, $target)),
            default => null,
        };
    }
}
