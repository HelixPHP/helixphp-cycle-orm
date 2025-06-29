<?php
namespace CAFernandes\ExpressPHP\CycleORM\Helpers;

class EnvironmentHelper
{
    public static function isProduction(): bool
    {
        return in_array(env('APP_ENV', 'production'), ['production', 'prod']);
    }

    public static function isDevelopment(): bool
    {
        return in_array(env('APP_ENV', 'development'), ['development', 'dev', 'local']);
    }

    public static function isTesting(): bool
    {
        return in_array(env('APP_ENV'), ['testing', 'test']);
    }

    public static function getEnvironment(): string
    {
        return env('APP_ENV', 'production');
    }
}