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
use Serendipity\Hyperf\Event\HttpHandleInterrupted;
use Serendipity\Hyperf\Event\HttpHandleStarted;
use Serendipity\Infrastructure\Http\RequestAdditionalFactory;
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
     *     context_lines?: null|int,
     *     default_integrations?: bool,
     *     dsn?: null|bool|Dsn|string,
     *     environment?: null|string,
     *     error_types?: null|int,
     *     http_client?: null|HttpClientInterface,
     *     http_compression?: bool,
     *     http_connect_timeout?: float|int,
     *     http_proxy?: null|string,
     *     http_proxy_authentication?: null|string,
     *     http_ssl_verify_peer?: bool,
     *     http_timeout?: float|int,
     *     ignore_exceptions?: array<class-string>,
     *     ignore_transactions?: array<string>,
     *     in_app_exclude?: array<string>,
     *     in_app_include?: array<string>,
     *     integrations?: callable(IntegrationInterface[]): IntegrationInterface[]|IntegrationInterface[],
     *     logger?: null|LoggerInterface,
     *     max_breadcrumbs?: int,
     *     max_request_body_size?: "always"|"medium"|"never"|"none"|"small",
     *     max_value_length?: int,
     *     prefixes?: array<string>,
     *     profiles_sample_rate?: null|float|int,
     *     release?: null|string,
     *     sample_rate?: float|int,
     *     send_attempts?: int,
     *     send_default_pii?: bool,
     *     server_name?: string,
     *     server_name?: string,
     *     spotlight?: bool,
     *     spotlight_url?: string,
     *     tags?: array<string>,
     *     trace_propagation_targets?: null|array<string>,
     *     traces_sample_rate?: null|float|int,
     *     traces_sampler?: null|callable,
     *     transport?: callable,
     * }
     */
    private readonly array $options;

    private readonly bool $debug;

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger,
        private readonly RequestAdditionalFactory $factory,
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
