<?php

if (!function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     */
    function app_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 3) . '/app';

        return $basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
