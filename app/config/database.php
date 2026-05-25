<?php
if (!function_exists('_env')) {
function _env(string $key, string $default = ''): string
{
    static $loaded = false;
    static $vars = [];
    if (!$loaded) {
        $envFile = base_path() . DIRECTORY_SEPARATOR . '.env';
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                if (!str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $vars[trim($k)] = trim($v);
            }
        }
        $loaded = true;
    }
    return $vars[$key] ?? $default;
}
}

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'      => 'mysql',
            'host'        => _env('DB_HOST', '127.0.0.1'),
            'port'        => _env('DB_PORT', '3306'),
            'database'    => _env('DB_DATABASE', 'wetalk'),
            'username'    => _env('DB_USERNAME', 'root'),
            'password'    => _env('DB_PASSWORD', ''),
            'charset'     => 'utf8mb4',
            'collation'   => 'utf8mb4_general_ci',
            'prefix'      => '',
            'strict'      => true,
            'engine'      => null,
            'options'     => [
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
                'heartbeat_interval' => 50,
            ],
        ],
    ],
];
