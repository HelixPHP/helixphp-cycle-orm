<?php

// Helper functions para compatibilidade
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string {
        $basePath = dirname(__DIR__, 4); // Assumindo vendor/package/src/config
        return $basePath . '/app' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('database_path')) {
    function database_path(string $path = ''): string {
        $basePath = dirname(__DIR__, 4);
        return $basePath . '/database' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string {
        $basePath = dirname(__DIR__, 4);
        return $basePath . '/config' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

return [
    // CORREÇÃO: Configuração de database mais robusta
    'database' => [
        'default' => env('DB_CONNECTION', 'mysql'),
        'databases' => [
            'default' => ['connection' => env('DB_CONNECTION', 'mysql')]
        ],
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', 'localhost'),
                'port' => (int) env('DB_PORT', 3306),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => env('DB_CHARSET', 'utf8mb4'),
                'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
                'timezone' => env('DB_TIMEZONE', '+00:00'),
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            ],
            'postgres' => [
                'driver' => 'postgres',
                'host' => env('DB_HOST', 'localhost'),
                'port' => (int) env('DB_PORT', 5432),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => env('DB_CHARSET', 'utf8'),
                'timezone' => env('DB_TIMEZONE', 'UTC'),
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => env('DB_DATABASE', database_path('database.sqlite')),
                'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            ]
        ]
    ],

    // CORREÇÃO: Configuração de entidades com fallbacks
    'entities' => [
        'directories' => [
            app_path('Models'),
            app_path('Entities'),
            // Fallback para estrutura alternativa
            dirname(__DIR__, 4) . '/src/Models',
        ],
        'namespace' => env('CYCLE_ENTITY_NAMESPACE', 'App\\Models')
    ],

    // CORREÇÃO: Schema com configurações mais detalhadas
    'schema' => [
        'cache' => (bool) env('CYCLE_SCHEMA_CACHE', true),
        'cache_key' => env('CYCLE_CACHE_KEY', 'cycle_schema'),
        'auto_sync' => (bool) env('CYCLE_AUTO_SYNC', false),
        'strict' => (bool) env('CYCLE_SCHEMA_STRICT', false),
        'warmup' => (bool) env('CYCLE_WARMUP', true),

        // NOVO: Configurações específicas de schema
        'generators' => [
            'reset_tables' => true,
            'validate_entities' => true,
            'render_tables' => true,
            'render_relations' => true,
        ]
    ],

    // CORREÇÃO: Migrations com configurações expandidas
    'migrations' => [
        'directory' => env('CYCLE_MIGRATIONS_PATH', database_path('migrations')),
        'table' => env('CYCLE_MIGRATIONS_TABLE', 'cycle_migrations'),
        'namespace' => env('CYCLE_MIGRATIONS_NAMESPACE', 'Database\\Migrations'),
        'safe' => (bool) env('CYCLE_SAFE_MIGRATIONS', true),
        'auto_run' => (bool) env('CYCLE_AUTO_MIGRATE', false)
    ],

    // NOVO: Performance settings expandidas
    'performance' => [
        'query_cache' => (bool) env('CYCLE_QUERY_CACHE', false),
        'lazy_loading' => (bool) env('CYCLE_LAZY_LOADING', true),
        'collection_factory' => env('CYCLE_COLLECTION_FACTORY', 'array'),
        'preload_relations' => (bool) env('CYCLE_PRELOAD_RELATIONS', false),

        // Connection pooling
        'connection_pool' => [
            'min_connections' => (int) env('CYCLE_MIN_CONNECTIONS', 1),
            'max_connections' => (int) env('CYCLE_MAX_CONNECTIONS', 10),
            'idle_timeout' => (int) env('CYCLE_IDLE_TIMEOUT', 60),
        ]
    ],

    // NOVO: Development settings expandidas
    'development' => [
        'log_queries' => (bool) env('CYCLE_LOG_QUERIES', false),
        'slow_query_threshold' => (int) env('CYCLE_SLOW_QUERY_MS', 100),
        'debug_mode' => (bool) env('CYCLE_DEBUG', false),
        'profile_queries' => (bool) env('CYCLE_PROFILE_QUERIES', false),

        // Schema validation
        'validate_schema_on_boot' => (bool) env('CYCLE_VALIDATE_SCHEMA', false),
        'schema_validation_strict' => (bool) env('CYCLE_SCHEMA_VALIDATION_STRICT', false),
    ],

    // NOVO: Security settings
    'security' => [
        'encrypt_connection' => (bool) env('DB_ENCRYPT', false),
        'verify_ssl' => (bool) env('DB_VERIFY_SSL', true),
        'connection_timeout' => (int) env('DB_TIMEOUT', 30),

        // Query security
        'disable_raw_queries' => (bool) env('CYCLE_DISABLE_RAW_QUERIES', false),
        'audit_queries' => (bool) env('CYCLE_AUDIT_QUERIES', false),
    ],

    // NOVO: Monitoring and observability
    'monitoring' => [
        'metrics_enabled' => (bool) env('CYCLE_METRICS_ENABLED', false),
        'slow_query_log' => (bool) env('CYCLE_SLOW_QUERY_LOG', false),
        'query_stats' => (bool) env('CYCLE_QUERY_STATS', false),

        // Health checks
        'health_check_enabled' => (bool) env('CYCLE_HEALTH_CHECK', true),
        'health_check_interval' => (int) env('CYCLE_HEALTH_CHECK_INTERVAL', 300),
    ]
];
