<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Repository;

use Constructo\Support\Datum;
use Constructo\Type\Collection;
use Serendipity\Domain\Contract\Adapter\Serializer;
use Throwable;

use function array_shift;
use function Constructo\Cast\arrayify;

/**
 * @template T of object
 */
abstract class Repository
{
    /**
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
     * @template U of Collection
     * @param Serializer<T> $serializer
     * @param class-string<U> $collection
     *
     * @return U
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
