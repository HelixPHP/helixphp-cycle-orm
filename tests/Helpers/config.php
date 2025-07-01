<?php

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        // Retorne valores de configuração fake conforme necessário
        $configs = [
            'cycle.transaction.auto_commit' => true,
        ];

        return $configs[$key] ?? $default;
    }
}
