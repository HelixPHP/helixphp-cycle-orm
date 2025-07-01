<?php

namespace CAFernandes\ExpressPHP\CycleORM\Helpers;

class EnvironmentHelper
{
    /**
     * Verifica se o ambiente é produção.
     *
     * @return bool
     */
    public static function isProduction(): bool
    {
        return in_array(env('APP_ENV', 'production'), ['production', 'prod'], true);
    }

    /**
     * Verifica se o ambiente é desenvolvimento.
     *
     * @return bool
     */
    public static function isDevelopment(): bool
    {
        return in_array(env('APP_ENV', 'development'), ['development', 'dev', 'local'], true);
    }

    /**
     * Verifica se o ambiente é de testes.
     *
     * @return bool
     */
    public static function isTesting(): bool
    {
        return in_array(env('APP_ENV', ''), ['testing', 'test'], true);
    }

    /**
     * Retorna o nome do ambiente atual.
     *
     * @return string
     */
    public static function getEnvironment(): string
    {
        $env = env('APP_ENV', 'production');
        return is_string($env) ? $env : 'production';
    }
}
