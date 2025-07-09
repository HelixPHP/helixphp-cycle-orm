<?php

namespace PivotPHP\CycleORM\Helpers;

class EnvironmentHelper
{
    /**
     * Cache centralizado para todos os métodos de ambiente.
     *
     * @var array<string, bool|string>
     */
    private static array $cache = [];
    /**
     * Verifica se o ambiente é produção.
     * Resultado é cached para melhor performance.
     */
    public static function isProduction(): bool
    {
        if (isset(self::$cache['isProduction'])) {
            return (bool) self::$cache['isProduction'];
        }

        $env = function_exists('env') ? env('APP_ENV', 'production') : ($_ENV['APP_ENV'] ?? 'production');
        self::$cache['isProduction'] = in_array($env, ['production', 'prod'], true);
        return (bool) self::$cache['isProduction'];
    }

    /**
     * Verifica se o ambiente é desenvolvimento.
     * Resultado é cached para melhor performance.
     */
    public static function isDevelopment(): bool
    {
        if (isset(self::$cache['isDevelopment'])) {
            return (bool) self::$cache['isDevelopment'];
        }

        $env = function_exists('env') ? env('APP_ENV', 'development') : ($_ENV['APP_ENV'] ?? 'development');
        self::$cache['isDevelopment'] = in_array($env, ['development', 'dev', 'local'], true);
        return (bool) self::$cache['isDevelopment'];
    }

    /**
     * Verifica se o ambiente é de testes.
     * Considera múltiplas fontes: APP_ENV, PHPUnit e variáveis de servidor.
     * Resultado é cached para melhor performance.
     */
    public static function isTesting(): bool
    {
        if (isset(self::$cache['isTesting'])) {
            return (bool) self::$cache['isTesting'];
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
                self::$cache['isTesting'] = true;
                return (bool) self::$cache['isTesting'];
            }
        }

        // Check if running under PHPUnit
        self::$cache['isTesting'] = defined('PHPUNIT_RUNNING') ||
                                   (isset($_ENV['PHPUNIT_RUNNING']) && $_ENV['PHPUNIT_RUNNING']) ||
                                   (isset($_SERVER['PHPUNIT_RUNNING']) && $_SERVER['PHPUNIT_RUNNING']);

        return (bool) self::$cache['isTesting'];
    }

    /**
     * Retorna o nome do ambiente atual.
     * Resultado é cached para melhor performance.
     */
    public static function getEnvironment(): string
    {
        if (isset(self::$cache['getEnvironment'])) {
            return (string) self::$cache['getEnvironment'];
        }

        $env = function_exists('env') ? env('APP_ENV', 'production') : ($_ENV['APP_ENV'] ?? 'production');
        self::$cache['getEnvironment'] = is_string($env) ? $env : 'production';
        return (string) self::$cache['getEnvironment'];
    }

    /**
     * Limpa o cache dos métodos de ambiente.
     * Útil para testes unitários ou quando variáveis de ambiente mudam.
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
