<?php
if (!function_exists('config')) {
    function config($key = null, $default = null) {
        // Retorne valores de configuração fake conforme necessário
        $configs = [
            'cycle.transaction.auto_commit' => true,
        ];
        return $configs[$key] ?? $default;
    }
}
