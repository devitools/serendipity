<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Logging;

use Google\Cloud\Logging\LoggingClient;
use Hyperf\Contract\ConfigInterface;
use Serendipity\Domain\Support\Task;
use Serendipity\Infrastructure\Logging\GoogleCloudLogger;

use function Constructo\Cast\arrayify;
use function Constructo\Cast\stringify;

readonly class GoogleCloudLoggerFactory
{
    private string $projectId;

    private string $serviceName;

    private string $format;

    private array $options;

    public function __construct(
        private Task $task,
        ConfigInterface $config,
    ) {
        $this->projectId = stringify($config->get('logger.gcloud.project_id', 'unknown'));
        $this->serviceName = stringify($config->get('logger.gcloud.service_name', 'unknown'));
        $this->format = stringify(
            $config->get('logger.gcloud.format', '{{message}} | {{resource}} | {{correlation_id}} | {{invoker_id}}')
        );
        $this->options = arrayify($config->get('logger.gcloud.options'));
    }

    public function make(string $env = 'dev'): GoogleCloudLogger
    {
        $logging = new LoggingClient(['projectId' => $this->projectId]);
        $driver = $logging->logger('google-cloud', $this->options);
        return new GoogleCloudLogger(
            $driver,
            $this->task,
            $this->format,
            $this->projectId,
            $this->serviceName,
            $env
        );
    }
}
