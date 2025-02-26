<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Persistence\Factory;

use Hyperf\Contract\ConfigInterface;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\InvalidConfigurationException;
use SleekDB\Exceptions\IOException;
use SleekDB\Store;

use function Serendipity\Type\Cast\toArray;
use function Serendipity\Type\Cast\toString;

class SleekDBDatabaseFactory
{
    public function __construct(private readonly ConfigInterface $config)
    {
    }

    /**
     * @throws InvalidConfigurationException
     * @throws InvalidArgumentException
     * @throws IOException
     */
    public function make(string $resource): Store
    {
        $config = toArray($this->config->get('databases.sleek'));
        $path = toString($config['path'] ?? '');
        $configuration = toArray($config['configuration'] ?? []);
        return new Store($resource, $path, $configuration);
    }
}
