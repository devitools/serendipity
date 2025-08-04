<?php

declare(strict_types=1);

namespace Serendipity\Hyperf\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Psr\Log\LoggerInterface;
use Sentry\Dsn;
use Sentry\HttpClient\HttpClientInterface;
use Sentry\Integration\IntegrationInterface;
use Sentry\State\Scope;
use Serendipity\Domain\Exception\Parser\AdditionalFactory;
use Serendipity\Hyperf\Event\HttpHandleInterrupted;
use Serendipity\Hyperf\Event\HttpHandleStarted;
use Throwable;

use function Constructo\Cast\arrayify;
use function Constructo\Cast\boolify;
use function Sentry\captureException;
use function Sentry\configureScope;
use function Sentry\init;

class SentryHttpListener implements ListenerInterface
{
    public const array EVENTS = [
        BootApplication::class,
        HttpHandleStarted::class,
        HttpHandleInterrupted::class,
    ];

    /**
     * @var array{
     *     attach_metric_code_locations?: bool,
     *     attach_stacktrace?: bool,
     *     before_breadcrumb?: callable,
     *     before_send?: callable,
     *     before_send_check_in?: callable,
     *     before_send_transaction?: callable,
     *     capture_silenced_errors?: bool,
     *     context_lines?: int|null,
     *     default_integrations?: bool,
     *     dsn?: string|bool|null|Dsn,
     *     environment?: string|null,
     *     error_types?: int|null,
     *     http_client?: HttpClientInterface|null,
     *     http_compression?: bool,
     *     http_connect_timeout?: int|float,
     *     http_proxy?: string|null,
     *     http_proxy_authentication?: string|null,
     *     http_ssl_verify_peer?: bool,
     *     http_timeout?: int|float,
     *     ignore_exceptions?: array<class-string>,
     *     ignore_transactions?: array<string>,
     *     in_app_exclude?: array<string>,
     *     in_app_include?: array<string>,
     *     integrations?: IntegrationInterface[]|callable(IntegrationInterface[]): IntegrationInterface[],
     *     logger?: LoggerInterface|null,
     *     max_breadcrumbs?: int,
     *     max_request_body_size?: "none"|"never"|"small"|"medium"|"always",
     *     max_value_length?: int,
     *     prefixes?: array<string>,
     *     profiles_sample_rate?: int|float|null,
     *     release?: string|null,
     *     sample_rate?: float|int,
     *     send_attempts?: int,
     *     send_default_pii?: bool,
     *     server_name?: string,
     *     server_name?: string,
     *     spotlight?: bool,
     *     spotlight_url?: string,
     *     tags?: array<string>,
     *     trace_propagation_targets?: array<string>|null,
     *     traces_sample_rate?: float|int|null,
     *     traces_sampler?: callable|null,
     *     transport?: callable,
     * }
     */
    private readonly array $options;

    private readonly bool $debug;

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger,
        private readonly AdditionalFactory $factory,
        private bool $booted = false,
    ) {
        $this->options = arrayify($this->config->get('sentry.options'));
        $this->debug = boolify($this->config->get('sentry.debug', false));
    }

    public function listen(): array
    {
        if (! isset($this->options['dsn'])) {
            return [];
        }
        $events = self::EVENTS;
        if (! $this->booted) {
            return $events;
        }
        if ($this->debug) {
            $this->logger->debug(sprintf("Sentry will listen to '%s' events", count($events)), $events);
        }
        return $events;
    }

    public function process(object $event): void
    {
        match (true) {
            $event instanceof BootApplication => $this->booted = true,
            $event instanceof HttpHandleStarted => $this->init($event),
            $event instanceof HttpHandleInterrupted => $this->capture($event),
            default => $this->fallback($event),
        };
    }

    private function init(HttpHandleStarted $event): void
    {
        if (! $this->booted) {
            return;
        }

        try {
            init($this->options);
            if ($this->debug) {
                $this->logger->debug('Sentry initialized', $this->options);
            }
        } catch (Throwable $exception) {
            $additional = $this->factory->make($event->request, $exception);
            $this->logger->emergency('Sentry initialization failed', ['exception' => $additional->message]);
        }
    }

    private function capture(HttpHandleInterrupted $event): void
    {
        if (! $this->booted) {
            return;
        }

        $additional = $this->factory->make($event->request, $event->exception);
        $context = $additional->context();
        configureScope(function (Scope $scope) use ($additional, $context): void {
            foreach ($context as $key => $value) {
                $scope->setExtra($key, $value);
            }
            $scope->setExtra('details', $additional->message);
        });
        if ($this->debug) {
            $this->logger->debug('Sentry captured exception', ['exception' => $additional->message]);
        }
        captureException($event->exception);
    }

    private function fallback(object $event): void
    {
        if (! $this->booted) {
            return;
        }
        $this->logger->warning('Sentry integration does not support this event', ['event' => $event::class]);
    }
}
