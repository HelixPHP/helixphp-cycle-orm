
# Configuration Guide - Express-PHP Cycle ORM Extension

## 🔧 Environment Variables

### Database Configuration

```env
# Primary database connection
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=express_api
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_TIMEZONE=+00:00

# Connection options
DB_FOREIGN_KEYS=true
DB_ENCRYPT=false
DB_VERIFY_SSL=true
DB_TIMEOUT=30
```

### Cycle ORM Specific

```env
# Schema configuration
CYCLE_SCHEMA_CACHE=true
CYCLE_CACHE_KEY=cycle_schema
CYCLE_AUTO_SYNC=false
CYCLE_SCHEMA_STRICT=false
CYCLE_WARMUP=true

# Entity configuration
CYCLE_ENTITY_NAMESPACE=App\Models

# Migration configuration
CYCLE_MIGRATIONS_PATH=database/migrations
CYCLE_MIGRATIONS_TABLE=cycle_migrations
CYCLE_MIGRATIONS_NAMESPACE=Database\Migrations
CYCLE_SAFE_MIGRATIONS=true
CYCLE_AUTO_MIGRATE=false

# Performance settings
CYCLE_QUERY_CACHE=false
CYCLE_LAZY_LOADING=true
CYCLE_COLLECTION_FACTORY=array
CYCLE_PRELOAD_RELATIONS=false

# Development settings
CYCLE_LOG_QUERIES=false
CYCLE_SLOW_QUERY_MS=100
CYCLE_DEBUG=false
CYCLE_PROFILE_QUERIES=false
CYCLE_VALIDATE_SCHEMA=false

# Security settings
CYCLE_DISABLE_RAW_QUERIES=false
CYCLE_AUDIT_QUERIES=false

# Monitoring settings
CYCLE_METRICS_ENABLED=false
CYCLE_SLOW_QUERY_LOG=false
CYCLE_QUERY_STATS=false
CYCLE_HEALTH_CHECK=true
CYCLE_HEALTH_CHECK_INTERVAL=300
```

## ⚙️ Configuration File

### Basic Configuration (config/cycle.php)

```php
<?php

return [
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

    'entities' => [
        'directories' => [
            app_path('Models'),
            app_path('Entities'),
        ],
        'namespace' => env('CYCLE_ENTITY_NAMESPACE', 'App\\Models')
    ],

    'schema' => [
        'cache' => (bool) env('CYCLE_SCHEMA_CACHE', true),
        'cache_key' => env('CYCLE_CACHE_KEY', 'cycle_schema'),
        'auto_sync' => (bool) env('CYCLE_AUTO_SYNC', false),
        'strict' => (bool) env('CYCLE_SCHEMA_STRICT', false),
        'warmup' => (bool) env('CYCLE_WARMUP', true),
    ],

    'migrations' => [
        'directory' => env('CYCLE_MIGRATIONS_PATH', database_path('migrations')),
        'table' => env('CYCLE_MIGRATIONS_TABLE', 'cycle_migrations'),
        'namespace' => env('CYCLE_MIGRATIONS_NAMESPACE', 'Database\\Migrations'),
        'safe' => (bool) env('CYCLE_SAFE_MIGRATIONS', true),
        'auto_run' => (bool) env('CYCLE_AUTO_MIGRATE', false)
    ],

    'performance' => [
        'query_cache' => (bool) env('CYCLE_QUERY_CACHE', false),
        'lazy_loading' => (bool) env('CYCLE_LAZY_LOADING', true),
        'collection_factory' => env('CYCLE_COLLECTION_FACTORY', 'array'),
        'preload_relations' => (bool) env('CYCLE_PRELOAD_RELATIONS', false),
    ],

    'development' => [
        'log_queries' => (bool) env('CYCLE_LOG_QUERIES', false),
        'slow_query_threshold' => (int) env('CYCLE_SLOW_QUERY_MS', 100),
        'debug_mode' => (bool) env('CYCLE_DEBUG', false),
        'profile_queries' => (bool) env('CYCLE_PROFILE_QUERIES', false),
    ]
];
```

### Multi-Environment Configuration

#### Production (config/cycle.production.php)

```php
<?php

return [
    'schema' => [
        'cache' => true,
        'auto_sync' => false,
        'strict' => true,
    ],

    'performance' => [
        'query_cache' => true,
        'lazy_loading' => true,
        'preload_relations' => true,
    ],

    'development' => [
        'log_queries' => false,
        'debug_mode' => false,
        'profile_queries' => false,
    ],

    'security' => [
        'disable_raw_queries' => true,
        'audit_queries' => true,
    ]
];
```

#### Development (config/cycle.development.php)

```php
<?php

return [
    'schema' => [
        'cache' => false,
        'auto_sync' => true,
        'strict' => false,
    ],

    'development' => [
        'log_queries' => true,
        'slow_query_threshold' => 50,
        'debug_mode' => true,
        'profile_queries' => true,
        'validate_schema_on_boot' => true,
    ]
];
```

#### Testing (config/cycle.testing.php)

```php
<?php

return [
    'database' => [
        'default' => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => true,
            ]
        ]
    ],

    'schema' => [
        'cache' => false,
        'auto_sync' => true,
    ],

    'migrations' => [
        'auto_run' => true,
    ]
];
```

## 🏗️ Multiple Database Connections

```php
return [
    'database' => [
        'default' => 'mysql',
        'databases' => [
            'default' => ['connection' => 'mysql'],
            'analytics' => ['connection' => 'postgres'],
            'cache' => ['connection' => 'redis'],
        ],
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST'),
                'database' => env('DB_DATABASE'),
                // ... mysql config
            ],
            'postgres' => [
                'driver' => 'postgres',
                'host' => env('ANALYTICS_DB_HOST'),
                'database' => env('ANALYTICS_DB_DATABASE'),
                // ... postgres config
            ],
            'redis' => [
                'driver' => 'redis',
                'host' => env('REDIS_HOST', 'localhost'),
                'port' => env('REDIS_PORT', 6379),
                // ... redis config
            ]
        ]
    ]
];
```

## 🔐 Security Configuration

### Database Security

```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST'),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            // SSL Configuration
            PDO::MYSQL_ATTR_SSL_CA => env('DB_SSL_CA'),
            PDO::MYSQL_ATTR_SSL_CERT => env('DB_SSL_CERT'),
            PDO::MYSQL_ATTR_SSL_KEY => env('DB_SSL_KEY'),
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => env('DB_SSL_VERIFY', false),
        ]
    ]
]
```

### Query Security

```php
'security' => [
    // Disable raw SQL queries in production
    'disable_raw_queries' => env('CYCLE_DISABLE_RAW_QUERIES', false),

    // Enable query auditing
    'audit_queries' => env('CYCLE_AUDIT_QUERIES', false),

    // Maximum query execution time (seconds)
    'max_query_time' => env('CYCLE_MAX_QUERY_TIME', 30),

    // SQL injection protection
    'sanitize_inputs' => true,

    // Rate limiting per entity
    'rate_limits' => [
        'default' => ['requests' => 1000, 'period' => 3600], // 1000 per hour
        'User' => ['requests' => 100, 'period' => 60], // 100 per minute
    ]
]
```

## 📊 Performance Tuning

### Connection Pooling

```php
'performance' => [
    'connection_pool' => [
        'min_connections' => (int) env('CYCLE_MIN_CONNECTIONS', 1),
        'max_connections' => (int) env('CYCLE_MAX_CONNECTIONS', 10),
        'idle_timeout' => (int) env('CYCLE_IDLE_TIMEOUT', 60),
        'max_lifetime' => (int) env('CYCLE_MAX_LIFETIME', 3600),
    ],

    'query_optimization' => [
        'enable_query_cache' => env('CYCLE_QUERY_CACHE', false),
        'cache_ttl' => (int) env('CYCLE_CACHE_TTL', 300),
        'optimize_joins' => true,
        'use_prepared_statements' => true,
    ]
]
```

### Memory Management

```php
'memory' => [
    'max_entities_in_memory' => (int) env('CYCLE_MAX_ENTITIES', 10000),
    'garbage_collection_threshold' => (int) env('CYCLE_GC_THRESHOLD', 1000),
    'clear_identity_map_frequency' => (int) env('CYCLE_CLEAR_FREQUENCY', 100),
]
```

## 🔍 Monitoring Configuration

```php
'monitoring' => [
    'metrics' => [
        'enabled' => (bool) env('CYCLE_METRICS_ENABLED', false),
        'endpoint' => env('CYCLE_METRICS_ENDPOINT', '/metrics'),
        'include_query_stats' => true,
        'include_memory_usage' => true,
    ],

    'logging' => [
        'slow_queries' => [
            'enabled' => (bool) env('CYCLE_SLOW_QUERY_LOG', false),
            'threshold_ms' => (int) env('CYCLE_SLOW_QUERY_MS', 100),
            'log_level' => 'warning',
        ],
        'failed_queries' => [
            'enabled' => true,
            'log_level' => 'error',
            'include_stack_trace' => true,
        ]
    ],

    'health_checks' => [
        'enabled' => (bool) env('CYCLE_HEALTH_CHECK', true),
        'interval' => (int) env('CYCLE_HEALTH_CHECK_INTERVAL', 300),
        'endpoint' => '/health/cycle',
        'timeout' => 5,
    ]
]
```

## 🔄 Custom Configuration Providers

### Custom Config Provider

```php
namespace App\Config;

class CycleConfigProvider
{
    public static function getDatabaseConfig(): array
    {
        $config = config('cycle.database');

        // Runtime configuration adjustments
        if (app()->environment('testing')) {
            $config['connections']['sqlite'] = [
                'driver' => 'sqlite',
                'database' => ':memory:'
            ];
        }

        return $config;
    }

    public static function getPerformanceConfig(): array
    {
        $config = config('cycle.performance');

        // Adjust based on server resources
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit && $memoryLimit !== '-1') {
            $limitBytes = self::parseMemoryLimit($memoryLimit);
            $config['max_entities_in_memory'] = min(
                $config['max_entities_in_memory'],
                (int) ($limitBytes / 1024 / 1024 / 2) // Half of memory limit in MB
            );
        }

        return $config;
    }

    private static function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $unit = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return $value;
        }
    }
}
```