<?php

if (!function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     */
    function app_path(string $path = ''): string
    {
        // Tentar encontrar o diretório app a partir de diferentes locais
        $possiblePaths = [
            getcwd() . '/app', // a partir do diretório atual
            getcwd() . '/src', // se usar src ao invés de app
            dirname(__DIR__, 4) . '/app', // a partir do vendor
            dirname(__DIR__, 4) . '/src', // a partir do vendor usando src
        ];

        $basePath = null;
        foreach ($possiblePaths as $possiblePath) {
            if (is_dir($possiblePath)) {
                $basePath = $possiblePath;
                break;
            }
        }

        // Se não encontrar, criar baseado no diretório atual
        if (!$basePath) {
            $basePath = getcwd() . '/app';
        }

        return $basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
