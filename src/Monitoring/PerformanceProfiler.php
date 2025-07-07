<?php

namespace PivotPHP\CycleORM\Monitoring;

/**
 * Profiler de performance para Cycle ORM.
 */
class PerformanceProfiler
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $profiles = [];

    /**
     * @var array<string, float>
     */
    private array $timings = [];

    private static bool $enabled = false;

    /**
     * Habilitar profiling.
     */
    public static function enable(): void
    {
        self::$enabled = true;
    }

    /**
     * Desabilitar profiling.
     */
    public static function disable(): void
    {
        self::$enabled = false;
    }

    /**
     * Iniciar profile.
     */
    public static function start(string $name): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$profiles[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'queries_before' => MetricsCollector::getMetrics()['queries_executed'] ?? 0,
        ];
    }

    /**
     * Iniciar timing para instância.
     */
    public function startTiming(string $name): void
    {
        $this->timings[$name] = microtime(true);
    }

    /**
     * Parar timing e retornar duração.
     */
    public function stop(string $name): float
    {
        if (!isset($this->timings[$name])) {
            return 0.0;
        }

        $elapsed = (microtime(true) - $this->timings[$name]) * 1000;
        $this->timings[$name] = $elapsed;

        return $elapsed;
    }

    /**
     * Retornar todos os timings.
     *
     * @return array<string, float>
     */
    public function getProfiles(): array
    {
        return $this->timings;
    }

    /**
     * Finalizar profile.
     *
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
            'timestamp' => date('c'),
        ];

        unset(self::$profiles[$name]);

        // Log perfis lentos
        if ($profile['duration_ms'] > 1000) {
            error_log("Slow Cycle ORM operation: {$name} - {$profile['duration_ms']}ms");
        }

        return $profile;
    }

    /**
     * Retorna todos os perfis ativos.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getActiveProfiles(): array
    {
        return self::$profiles;
    }

    /**
     * Registrar um profile manualmente.
     *
     * @param array<string, mixed> $profile
     */
    public static function profile(string $name, array $profile): void
    {
        self::$profiles[$name] = $profile;
    }

    /**
     * Verifica se o profiling está habilitado.
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Reseta todos os timings da instância.
     */
    public function reset(): void
    {
        $this->timings = [];
    }
}
