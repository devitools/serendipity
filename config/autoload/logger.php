<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Log\LogLevel;

use function Constructo\Cast\stringify;
use function Hyperf\Support\env;

$logLevel = stringify(env('STDOUT_LOG_LEVEL'));
$formatter = match (env('APP_ENV', 'dev')) {
    'dev' => [
        'class' => LineFormatter::class,
        'constructor' => [
            'format' => "||%datetime%||%channel%||%level_name%||%message%||%context%||%extra%\n",
            'allowInlineLineBreaks' => false,
            'includeStacktraces' => true,
        ],
    ],
    default => [
        'class' => JsonFormatter::class,
        'constructor' => [],
    ],
};

return [
    'default' => [
        'handler' => [
            'class' => StreamHandler::class,
            'constructor' => [
                'stream' => 'php://stdout',
                'level' => Level::Info,
            ],
        ],
        'formatter' => $formatter,
        'levels' => $logLevel
            ? explode(',', $logLevel)
            : [
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::NOTICE,
                LogLevel::INFO,
                LogLevel::DEBUG,
            ],
    ],

    'gcloud' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT', 'unknown'),
        'service_name' => env('GOOGLE_CLOUD_SERVICE_NAME', 'unknown'),
        'format' => '{{message}} | {{resource}} | {{correlation_id}} | {{invoker_id}}',
        'options' => [
            'batchEnabled' => true,
            'batchOptions' => [
                'batchSize' => 50,
                'callPeriod' => 5,
            ],
        ],
    ],
];
