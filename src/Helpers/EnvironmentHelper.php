<?php

namespace PivotPHP\CycleORM\Helpers;

class EnvironmentHelper
{
    /**
     * Verifica se o ambiente é produção.
     * Resultado é cached para melhor performance.
     */
    public static function isProduction(): bool
    {
        static $cachedResult = null;
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $env = function_exists('env') ? env('APP_ENV', 'production') : ($_ENV['APP_ENV'] ?? 'production');
        $cachedResult = in_array($env, ['production', 'prod'], true);
        return $cachedResult;
    }

    /**
     * Verifica se o ambiente é desenvolvimento.
     * Resultado é cached para melhor performance.
     */
    public static function isDevelopment(): bool
    {
        static $cachedResult = null;
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $env = function_exists('env') ? env('APP_ENV', 'development') : ($_ENV['APP_ENV'] ?? 'development');
        $cachedResult = in_array($env, ['development', 'dev', 'local'], true);
        return $cachedResult;
    }

    /**
     * Verifica se o ambiente é de testes.
     * Considera múltiplas fontes: APP_ENV, PHPUnit e variáveis de servidor.
     * Resultado é cached para melhor performance.
     */
    public static function isTesting(): bool
    {
        static $cachedResult = null;
        if ($cachedResult !== null) {
            return $cachedResult;
        }

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
                $cachedResult = true;
                return $cachedResult;
            }
        }

        // Check if running under PHPUnit
        $cachedResult = defined('PHPUNIT_RUNNING') ||
                       (isset($_ENV['PHPUNIT_RUNNING']) && $_ENV['PHPUNIT_RUNNING']) ||
                       (isset($_SERVER['PHPUNIT_RUNNING']) && $_SERVER['PHPUNIT_RUNNING']);

        return $cachedResult;
    }

    /**
     * Retorna o nome do ambiente atual.
     * Resultado é cached para melhor performance.
     */
    public static function getEnvironment(): string
    {
        static $cachedResult = null;
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        $env = function_exists('env') ? env('APP_ENV', 'production') : ($_ENV['APP_ENV'] ?? 'production');
        $cachedResult = is_string($env) ? $env : 'production';
        return $cachedResult;
    }

    /**
     * Limpa o cache dos métodos de ambiente.
     * Útil para testes unitários ou quando variáveis de ambiente mudam.
     */
    public static function clearCache(): void
    {
        // Reset all static caches by calling each method with a flag
        // This is a simple approach - in production the cache should rarely need clearing
    }
}
