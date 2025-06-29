<?php

return [
    // Database configuration
    'database' => [
        'default' => env('DB_CONNECTION', 'mysql'),
        'databases' => [
            'default' => ['connection' => env('DB_CONNECTION', 'mysql')]
        ],
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', 'localhost'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'express_db'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => env('DB_DATABASE', 'database/database.sqlite'),
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            ]
        ]
    ],

    // Entity configuration
    'entities' => [
        'directories' => ['app/Models'],
        'namespace' => 'App\\Models'
    ],

    // Schema configuration
    'schema' => [
        'cache' => env('CYCLE_SCHEMA_CACHE', true),
        'auto_sync' => env('CYCLE_AUTO_SYNC', false),
        'strict' => env('CYCLE_SCHEMA_STRICT', false)
    ],

    // Migration configuration
    'migrations' => [
        'directory' => 'database/migrations',
        'table' => 'migrations',
        'safe' => env('CYCLE_SAFE_MIGRATIONS', true)
    ],

    // Repository configuration
    'repositories' => [
        'default' => \Cycle\ORM\Select\Repository::class
    ]
];