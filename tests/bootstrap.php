<?php

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestHelpers.php';

// Configurar environment de teste
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_ENV['CYCLE_SCHEMA_CACHE'] = 'false';

// Preparar diretórios de teste
$testDirs = [
    __DIR__ . '/temp',
    __DIR__ . '/Fixtures/Models',
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
