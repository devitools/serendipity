<?php

declare(strict_types=1);

use Constructo\Contract\Reflect\SpecsFactory;
use Constructo\Contract\Reflect\TypesFactory;
use Psr\Container\ContainerInterface as Container;
use Psr\Log\LoggerInterface;
use Serendipity\Example\Game\Domain\Repository\GameCommandRepository;
use Serendipity\Example\Game\Domain\Repository\GameQueryRepository;
use Serendipity\Example\Game\Infrastructure\Repository\SleekDB\SleekDBGameCommandRepository;
use Serendipity\Example\Game\Infrastructure\Repository\SleekDB\SleekDBGameQueryRepository;
use Serendipity\Hyperf\Database\Document\HyperfMongoFactory;
use Serendipity\Hyperf\Database\Document\HyperfSleekDBFactory;
use Serendipity\Hyperf\Database\Relational\HyperfConnectionFactory;
use Serendipity\Hyperf\Logging\GoogleCloudLoggerFactory;
use Serendipity\Hyperf\Logging\StdoutLoggerFactory;
use Serendipity\Hyperf\Support\HyperfSpecsFactory;
use Serendipity\Hyperf\Support\HyperfTypesFactory;
use Serendipity\Hyperf\Testing\Observability\Logger\InMemory\InMemoryLogger;
use Serendipity\Infrastructure\Database\Document\MongoFactory;
use Serendipity\Infrastructure\Database\Document\SleekDBFactory;
use Serendipity\Infrastructure\Database\Relational\ConnectionFactory;

use function Hyperf\Support\env;
use function Serendipity\Type\Cast\stringify;

if (! defined('APP_ENV')) {
    define('APP_ENV', env('APP_ENV', 'dev'));
}

return [
    LoggerInterface::class => fn (Container $container) => match (APP_ENV) {
        'test' => $container->get(InMemoryLogger::class),
        'prd', 'hom', 'liv', 'stg' => $container->get(GoogleCloudLoggerFactory::class)
            ->make(stringify(APP_ENV)),
        default => $container->get(StdoutLoggerFactory::class)
            ->make(stringify(APP_ENV)),
    },
    TypesFactory::class => HyperfTypesFactory::class,
    SpecsFactory::class => HyperfSpecsFactory::class,

    SleekDBFactory::class => fn (Container $container) => new HyperfSleekDBFactory($container),
    MongoFactory::class => fn (Container $container) => new HyperfMongoFactory($container),
    ConnectionFactory::class => HyperfConnectionFactory::class,

    GameCommandRepository::class => SleekDBGameCommandRepository::class,
    GameQueryRepository::class => SleekDBGameQueryRepository::class,
];
