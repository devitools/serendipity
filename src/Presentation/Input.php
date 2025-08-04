<?php

declare(strict_types=1);

namespace Serendipity\Presentation;

use Constructo\Contract\Message;
use Constructo\Support\Set;
use Psr\Container\ContainerInterface;
use Serendipity\Hyperf\Request\HyperfFormRequest;
use Serendipity\Presentation\Input\Mapped;
use Serendipity\Presentation\Input\Params;

/**
 * @see https://hyperf.wiki/3.1/#/en/validation?id=form-request-validation
 */
class Input extends HyperfFormRequest implements Message
{
    public function __construct(
        ContainerInterface $container,
        Set $properties = new Set([]),
        Set $values = new Set([]),
        /**
         * @var array<string, array|string>
         */
        protected readonly array $rules = [],
        /**
         * @var array<string, callable(mixed $value):mixed|string>
         */
        protected readonly array $mappings = [],
        protected readonly bool $authorize = true,
    ) {
        parent::__construct($container, $properties, $values);
    }

    public function content(): Set
    {
        return $this->values();
    }

    public function authorize(): bool
    {
        return $this->authorize;
    }

    /**
     * @return array<string, array|string>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @return array<string, callable(array $data):mixed|string>
     */
    public function mappings(): array
    {
        return $this->mappings;
    }

    protected function validationData(): array
    {
        return (new Mapped($this))
            ->then(new Params($this))
            ->resolve(parent::validationData());
    }
}
