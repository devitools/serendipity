<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Request;

use Constructo\Support\Set;
use Hyperf\Context\Context;
use Hyperf\Validation\Request\FormRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

use function Hyperf\Collection\data_get;

abstract class HyperfFormRequest extends FormRequest
{
    public function __construct(
        ContainerInterface $container,
        protected readonly Set $properties = new Set([]),
        protected readonly Set $values = new Set([]),
    ) {
        parent::__construct($container);
    }

    /**
     * @SuppressWarnings(StaticAccess)
     */
    final public function properties(): Set
    {
        if (Context::has(ServerRequestInterface::class)) {
            $headers = $this->getHeaders();
            $headers = $this->normalizeHeaders($headers);
            return $this->properties->along($headers);
        }
        return $this->properties;
    }

    /**
     * @SuppressWarnings(StaticAccess)
     */
    final public function values(): Set
    {
        if (Context::has(ServerRequestInterface::class)) {
            return $this->values->along($this->validated());
        }
        return $this->values;
    }

    /**
     * @deprecated Use `value(string $key, mixed $default = null): mixed` instead
     */
    final public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->values()->toArray();
        }
        return $this->value($key, $default);
    }

    /**
     * @template T of mixed
     * @param T $default
     *
     * @return T
     */
    final public function value(string $key, mixed $default = null): mixed
    {
        return $this->retrieve($this->values(), $key, $default);
    }

    /**
     * @deprecated Use `value(string $key, mixed $default = null): mixed` instead
     */
    final public function input(string $key, mixed $default = null): mixed
    {
        return $this->value($key, $default);
    }

    protected function retrieve(Set $data, string $key, mixed $default = null): mixed
    {
        return data_get($data->toArray(), $key, $default);
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(
            fn (mixed $value) => is_array($value) ? implode('; ', $value) : $value,
            $headers
        );
    }
}
