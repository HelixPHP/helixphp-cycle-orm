<?php

namespace PivotPHP\CycleORM\Helpers;

class EnvironmentHelper
{
    /**
     * Verifica se o ambiente é produção.
     */
    public static function isProduction(): bool
    {
        return in_array(env('APP_ENV', 'production'), ['production', 'prod'], true);
    }

    /**
     * Verifica se o ambiente é desenvolvimento.
     */
    public static function isDevelopment(): bool
    {
        return in_array(env('APP_ENV', 'development'), ['development', 'dev', 'local'], true);
    }

    /**
     * Verifica se o ambiente é de testes.
     * Considera múltiplas fontes: APP_ENV, PHPUnit e variáveis de servidor.
     */
    public static function isTesting(): bool
    {
        // Check APP_ENV from multiple sources
        $envSources = [
            $_ENV['APP_ENV'] ?? '',
            $_SERVER['APP_ENV'] ?? '',
        ];

        // Add env() function result if available
        if (function_exists('env')) {
            $envSources[] = env('APP_ENV', '');
        }

        foreach ($envSources as $envValue) {
            if (in_array($envValue, ['testing', 'test'], true)) {
                return true;
            }
        }

        // Check if running under PHPUnit
        return defined('PHPUNIT_RUNNING') ||
               (isset($_ENV['PHPUNIT_RUNNING']) && $_ENV['PHPUNIT_RUNNING']) ||
               (isset($_SERVER['PHPUNIT_RUNNING']) && $_SERVER['PHPUNIT_RUNNING']);
    }

    /**
     * Retorna o nome do ambiente atual.
     */
    public static function getEnvironment(): string
    {
        $env = env('APP_ENV', 'production');

        return is_string($env) ? $env : 'production';
    }
}
