<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'admission_test' => [
        'host' => env('TEST_VALKEY_HOST', 'valkey'),
        'port' => 6379,
        'db' => 0,
        'timeout' => 0.1,
        'read_timeout' => 0.1,
        'retry_interval' => 0,
        'pool' => [
            'min_connections' => 0,
            'max_connections' => 4,
            'connect_timeout' => 0.1,
            'wait_timeout' => 0.05,
            'heartbeat' => -1,
            'max_idle_time' => 60,
        ],
    ],
];
