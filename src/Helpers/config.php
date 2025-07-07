<?php

if (!function_exists('config')) {
    /**
     * Get configuration value.
     */
    function config(string $key, mixed $default = null): mixed
    {
        // Para compatibilidade, usar env como fallback
        $envKey = str_replace('.', '_', strtoupper($key));

        return env($envKey, $default);
    }
}
