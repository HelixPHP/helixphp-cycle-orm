<?php

namespace CAFernandes\ExpressPHP\CycleORM\Monitoring;

/**
 * Profiler de performance para Cycle ORM
 */
class PerformanceProfiler
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $profiles = [];
    private static bool $enabled = false;

    /**
     * Habilitar profiling
     * @return void
     */
    public static function enable(): void
    {
        self::$enabled = true;
    }

    /**
     * Desabilitar profiling
     * @return void
     */
    public static function disable(): void
    {
        self::$enabled = false;
    }

    /**
     * Iniciar profile
     * @param string $name
     * @return void
     */
    public static function start(string $name): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$profiles[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'queries_before' => 0
        ];
    }

    /**
     * Finalizar profile
     * @param string $name
     * @return array<string, mixed>
     */
    public static function end(string $name): array
    {
        if (!self::$enabled || !isset(self::$profiles[$name])) {
            return [];
        }

        $start = self::$profiles[$name];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $profile = [
            'name' => $name,
            'duration_ms' => round(($endTime - $start['start_time']) * 1000, 2),
            'memory_used_mb' => round(($endMemory - $start['start_memory']) / 1024 / 1024, 2),
            'timestamp' => date('c')
        ];

        unset(self::$profiles[$name]);

        // Log perfis lentos
        if ($profile['duration_ms'] > 1000) {
            error_log("Slow Cycle ORM operation: {$name} - {$profile['duration_ms']}ms");
        }

        return $profile;
    }

    /**
     * Retorna todos os perfis ativos
     * @return array<string, array<string, mixed>>
     */
    public static function getActiveProfiles(): array
    {
        return self::$profiles;
    }

    /**
     * Registrar um profile manualmente
     * @param string $name
     * @param array<string, mixed> $profile
     * @return void
     */
    public static function profile(string $name, array $profile): void
    {
        self::$profiles[$name] = $profile;
    }
}
