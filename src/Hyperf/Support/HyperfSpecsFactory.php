<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Support;

use Constructo\Core\Serialize\Builder;
use Constructo\Factory\DefaultSpecsFactory;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

readonly class HyperfSpecsFactory extends DefaultSpecsFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $builder = $container->get(Builder::class);
        $config = $container->get(ConfigInterface::class);
        $specs = $config->get('schema.specs', []);
        /** @var array $specs */
        $specs = is_array($specs)
            ? $specs
            : [];
        parent::__construct($builder, $specs);
    }
}
