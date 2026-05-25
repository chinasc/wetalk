<?php
return [
    'default' => [
        'host' => _env('REDIS_HOST', '127.0.0.1'),
        'password' => _env('REDIS_PASSWORD', '') ?: null,
        'port' => (int)_env('REDIS_PORT', '6379'),
        'database' => (int)_env('REDIS_DB', '0'),
        'pool' => [
            'max_connections' => 5,
            'min_connections' => 1,
            'wait_timeout' => 3,
            'idle_timeout' => 60,
            'heartbeat_interval' => 50,
        ],
    ]
];
