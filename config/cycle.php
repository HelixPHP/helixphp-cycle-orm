<?php

return [
    // CORREÇÃO: Configuração de database mais completa
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
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'timezone' => env('DB_TIMEZONE', '+00:00'),
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]
            ],
            'postgres' => [
                'driver' => 'postgres',
                'host' => env('DB_HOST', 'localhost'),
                'port' => (int) env('DB_PORT', 5432),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'timezone' => env('DB_TIMEZONE', 'UTC'),
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => env('DB_DATABASE', database_path('database.sqlite')),
                'foreign_key_constraints' => true,
            ]
        ]
    ],

    // CORREÇÃO: Configuração de entidades melhorada
    'entities' => [
        'directories' => [
            app_path('Models'),
            app_path('Entities')
        ],
        'namespace' => 'App\\Models'
    ],

    // CORREÇÃO: Schema com mais opções
    'schema' => [
        'cache' => env('CYCLE_SCHEMA_CACHE', true),
        'cache_key' => 'cycle_schema',
        'auto_sync' => env('CYCLE_AUTO_SYNC', false),
        'strict' => env('CYCLE_SCHEMA_STRICT', false),
        'warmup' => env('CYCLE_WARMUP', true)
    ],

    // CORREÇÃO: Migrations melhoradas
    'migrations' => [
        'directory' => database_path('migrations'),
        'table' => 'cycle_migrations',
        'safe' => env('CYCLE_SAFE_MIGRATIONS', true),
        'auto_run' => env('CYCLE_AUTO_MIGRATE', false)
    ],

    // NOVO: Performance settings
    'performance' => [
        'query_cache' => env('CYCLE_QUERY_CACHE', false),
        'lazy_loading' => env('CYCLE_LAZY_LOADING', true),
        'collection_factory' => 'array'
    ],

    // NOVO: Development settings
    'development' => [
        'log_queries' => env('CYCLE_LOG_QUERIES', false),
        'slow_query_threshold' => (int) env('CYCLE_SLOW_QUERY_MS', 100),
        'debug_mode' => env('CYCLE_DEBUG', false)
    ]
];