<?php
if (!function_exists('config')) {
    function config($key = null, $default = null) {
        // Tenta buscar do container global, se existir
        if (function_exists('app') && app() && method_exists(app(), 'make')) {
            $configService = app()->make('config');
            if ($configService && method_exists($configService, 'get')) {
                return $configService->get($key, $default);
            }
        }
        // Fallback: retorna default
        return $default;
    }
}
