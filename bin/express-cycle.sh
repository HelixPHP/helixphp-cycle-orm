#!/usr/bin/env php
<?php

/**
 * Express-PHP Cycle ORM Extension CLI
 * Entry point para comandos da extensão
 */

// Auto-detect project root
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',         // Quando instalado via composer
    __DIR__ . '/../../../autoload.php',         // Quando é dependência de um projeto
    __DIR__ . '/../../../../vendor/autoload.php' // Estrutura alternativa
];

$autoloadFound = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    fwrite(STDERR, "Error: Composer autoload not found. Run 'composer install' first.\n");
    exit(1);
}

use CAFernandes\ExpressPHP\CycleORM\Commands\CommandRegistry;
use CAFernandes\ExpressPHP\CycleORM\Commands\EntityCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\SchemaCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\MigrateCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\StatusCommand;

// Verificar se estamos em um projeto Express-PHP
function findExpressApp(): ?object
{
    $possiblePaths = [
        getcwd() . '/app.php',
        getcwd() . '/public/index.php',
        getcwd() . '/index.php',
        getcwd() . '/bootstrap/app.php'
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            try {
                // Tentar carregar a aplicação
                $app = require $path;
                if (is_object($app)) {
                    return $app;
                }
            } catch (\Exception $e) {
                // Continuar tentando outros arquivos
                continue;
            }
        }
    }

    return null;
}

// Configurar aplicação global se disponível
$app = findExpressApp();
if ($app) {
    // Disponibilizar globalmente para os commands
    $GLOBALS['app'] = $app;

    if (!function_exists('app')) {
        function app() {
            return $GLOBALS['app'];
        }
    }
}

// Configurar registry de commands
$registry = new CommandRegistry();
$registry->register('make:entity', EntityCommand::class);
$registry->register('cycle:schema', SchemaCommand::class);
$registry->register('cycle:migrate', MigrateCommand::class);
$registry->register('cycle:status', StatusCommand::class);

// Processar argumentos da linha de comando
$args = array_slice($argv, 1);

if (empty($args)) {
    echo "Express-PHP Cycle ORM Extension CLI\n";
    echo "=====================================\n\n";
    echo "Available commands:\n";
    foreach ($registry->getRegisteredCommands() as $command) {
        echo "  {$command}\n";
    }
    echo "\nUsage: express-cycle <command> [options]\n";
    echo "Example: express-cycle make:entity User\n";
    exit(0);
}

$commandName = array_shift($args);

// Processar argumentos e opções
$parsedArgs = [];
$currentKey = null;

foreach ($args as $arg) {
    if (strpos($arg, '--') === 0) {
        $currentKey = substr($arg, 2);
        $parsedArgs[$currentKey] = true;
    } elseif ($currentKey !== null) {
        $parsedArgs[$currentKey] = $arg;
        $currentKey = null;
    } else {
        // Argumentos posicionais
        if ($commandName === 'make:entity' && !isset($parsedArgs['name'])) {
            $parsedArgs['name'] = $arg;
        }
    }
}

// Executar comando
$exitCode = $registry->run($commandName, $parsedArgs);
exit($exitCode);