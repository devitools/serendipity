<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository;

use Serendipity\Domain\Collection\Collection;
use Serendipity\Domain\Contract\Adapter\Serializer;
use Constructo\Support\Entity;
use Serendipity\Domain\Support\Datum;
use Throwable;

use function array_shift;
use function Serendipity\Type\Cast\arrayify;

abstract class Repository
{
    /**
     * @template T of Entity
     * @param Serializer<T> $serializer
     *
     * @return null|T
     */
    protected function entity(Serializer $serializer, array $data): mixed
    {
        if (empty($data)) {
            return null;
        }
        $datum = array_shift($data);
        $datum = $this->normalize($datum);
        return $serializer->serialize($datum);
    }

    /**
     * @template T of Collection
     * @param class-string<T> $collection
     *
     * @return T
     */
    protected function collection(Serializer $serializer, array $data, string $collection): mixed
    {
        $instance = new $collection();
        foreach ($data as $datum) {
            $datum = $this->normalize($datum);
            $datum = $this->serialize($serializer, $datum);
            $instance->push($datum);
        }
        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalize(mixed $datum): array
    {
        return arrayify($datum);
    }

    /**
     * @param array<string, mixed> $datum
     */
    private function serialize(Serializer $serializer, array $datum): object
    {
        try {
            return $serializer->serialize($datum);
        } catch (Throwable $exception) {
            return new Datum($exception, $datum);
        }
    }
}
