<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Logging;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Serendipity\Infrastructure\Logging\StdoutLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

use function Serendipity\Type\Cast\arrayify;

readonly class StdoutLoggerFactory
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function make(string $env = 'dev'): LoggerInterface
    {
        $output = $this->container->get(ConsoleOutput::class);
        $config = $this->container->get(ConfigInterface::class);
        $format = '[{{env}}.{{level}}] {{message}}: {{context}}';
        $default = [
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::EMERGENCY,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];
        $levels = arrayify($config->get('logger.default.levels', $default));
        return new StdoutLogger($output, $levels, $format, $env);
    }
}
