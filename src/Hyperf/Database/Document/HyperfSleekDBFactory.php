<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Document;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Serendipity\Infrastructure\Database\Document\SleekDBFactory;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\InvalidConfigurationException;
use SleekDB\Exceptions\IOException;
use SleekDB\Store;

use function Constructo\Cast\arrayify;
use function Constructo\Util\extractArray;
use function Constructo\Util\extractString;

readonly class HyperfSleekDBFactory implements SleekDBFactory
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws InvalidConfigurationException
     * @throws NotFoundExceptionInterface
     */
    public function make(string $resource): Store
    {
        $config = $this->container->get(ConfigInterface::class);
        $options = arrayify($config->get('databases.sleek'));
        $path = extractString($options, 'path');
        $configuration = extractArray($options, 'configuration');
        return new Store($resource, $path, $configuration);
    }
}
