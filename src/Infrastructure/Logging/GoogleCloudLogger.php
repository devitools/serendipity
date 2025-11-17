<?php

declare(strict_types=1);

namespace Serendipity\Infrastructure\Logging;

use DateTimeImmutable;
use DateTimeInterface;
use Google\Cloud\Logging\Entry;
use Google\Cloud\Logging\Logger as GoogleCloud;
use Psr\Log\LogLevel;
use Serendipity\Domain\Support\Task;
use Stringable;
use Throwable;

use function Constructo\Cast\stringify;
use function Constructo\Json\encode;
use function Serendipity\Runtime\coroutine;

class GoogleCloudLogger extends AbstractLogger
{
    public function __construct(
        private readonly GoogleCloud $driver,
        private readonly Task $task,
        private readonly string $format,
        public readonly string $projectId,
        public readonly string $serviceName,
        public readonly string $env,
        public readonly string $type = 'cloud_run_revision',
        public readonly string $location = 'us-central1',
        public readonly bool $useCoroutine = true,
    ) {
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $severity = $this->level(stringify($level));
        $context['message'] = $message;
        $payload = $this->payload($severity, $context);

        $message = $this->message(
            $this->format,
            $message,
            [
                ...$payload,
                ...$this->task->toArray(),
            ]
        );
        $write = fn () => $this->write($severity, $message, $payload);

        $this->useCoroutine
            ? coroutine($write)
            : $write();
    }

    protected function level(string $level): string
    {
        $levels = [
            LogLevel::EMERGENCY => 'EMERGENCY',
            LogLevel::ALERT => 'ALERT',
            LogLevel::CRITICAL => 'CRITICAL',
            LogLevel::ERROR => 'ERROR',
            LogLevel::WARNING => 'WARNING',
            LogLevel::NOTICE => 'NOTICE',
            LogLevel::INFO => 'INFO',
            LogLevel::DEBUG => 'DEBUG',
        ];
        return $levels[$level] ?? $levels[LogLevel::INFO];
    }

    private function payload(string $severity, array $context): array
    {
        return [
            'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'logName' => $this->logName(),
            'severity' => $severity,
            'jsonPayload' => $context,
            'resource' => [
                'labels' => [
                    'configuration_name' => $this->serviceName,
                    'location' => $this->location,
                    'service_name' => $this->serviceName,
                    'project_id' => $this->projectId,
                ],
                'type' => $this->type,
            ],
        ];
    }

    private function logName(): string
    {
        return sprintf('projects/%s/logs/%s%%2F%s-%s', $this->projectId, $this->serviceName, 'env', $this->env);
    }

    private function write(string $severity, string|Stringable $message, array $context): void
    {
        try {
            $this->driver->write(new Entry($context));
        } catch (Throwable $error) {
            $detail = sprintf('"%s" in `%s` at `%s`', $error->getMessage(), $error->getFile(), $error->getLine());
            $stdout = sprintf(
                '[panic] <%s> %s: %s (%s)',
                $severity,
                $message,
                encode($context),
                $detail
            );
            printf("%s\n", $stdout);
        }
    }
}
