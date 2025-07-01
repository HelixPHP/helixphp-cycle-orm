<?php

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Helper functions para testes
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
      // Mock básico para testes
        static $config = [];
        return $config[$key] ?? $default;
    }
}

// Configurar environment de teste
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_ENV['CYCLE_SCHEMA_CACHE'] = 'false';

// Preparar diretórios de teste
$testDirs = [
    __DIR__ . '/temp',
    __DIR__ . '/Fixtures/Models'
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
