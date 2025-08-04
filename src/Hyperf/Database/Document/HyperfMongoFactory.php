<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Database\Document;

use Hyperf\Contract\ConfigInterface;
use MongoDB\Client;
use MongoDB\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Serendipity\Infrastructure\Database\Document\MongoFactory;

use function Constructo\Cast\stringify;

readonly class HyperfMongoFactory implements MongoFactory
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function make(string $resource): Collection
    {
        $config = $this->container->get(ConfigInterface::class);
        $uri = stringify($config->get('databases.mongo.uri'));
        $database = stringify($config->get('databases.mongo.database'));
        return (new Client($uri))
            ->selectDatabase($database)
            ->selectCollection($resource);
    }
}
