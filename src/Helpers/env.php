<?php

if (!function_exists('env')) {
    /**
     * Helper para variáveis de ambiente.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (false === $value) {
            return $default;
        }

        return $value;
    }
}
