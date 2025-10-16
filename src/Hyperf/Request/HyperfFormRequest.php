<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Request;

use Constructo\Support\Set;
use Hyperf\Context\Context;
use Hyperf\Contract\ValidatorInterface;
use Hyperf\Validation\Request\FormRequest;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Serendipity\Domain\Event\ValidationFailedEvent;

use function Constructo\Cast\stringify;
use function Hyperf\Collection\data_get;

abstract class HyperfFormRequest extends FormRequest
{
    private readonly EventDispatcherInterface $eventDispatcher;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ContainerInterface $container,
        protected readonly Set $properties = new Set([]),
        protected readonly Set $values = new Set([]),
    ) {
        parent::__construct($container);

        $this->eventDispatcher = $container->get(EventDispatcherInterface::class);
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
            return $this->values()
                ->toArray();
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

    protected function failedValidation(ValidatorInterface $validator): void
    {
        $resource = sprintf('http::%s:%s', strtoupper($this->getMethod()), $this->getRequestUri());
        $values = Set::createFrom($this->all());
        $message = stringify($validator->errors());
        $this->eventDispatcher->dispatch(new ValidationFailedEvent($resource, $values, $message));
        parent::failedValidation($validator);
    }

    protected function retrieve(Set $data, string $key, mixed $default = null): mixed
    {
        return data_get($data->toArray(), $key, $default);
    }

    private function normalizeHeaders(array $headers): array
    {
        $callback = fn (mixed $value) => is_array($value)
            ? implode('; ', $value)
            : $value;
        return array_map($callback, $headers);
    }
}
