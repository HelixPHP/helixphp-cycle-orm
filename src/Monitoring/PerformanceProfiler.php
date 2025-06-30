<?php

namespace CAFernandes\ExpressPHP\CycleORM\Monitoring;

/**
 * Profiler de performance para Cycle ORM
 */
class PerformanceProfiler
{
  private static array $profiles = [];
  private static bool $enabled = false;

  /**
   * Habilitar profiling
   */
  public static function enable(): void
  {
    self::$enabled = true;
  }

  /**
   * Desabilitar profiling
   */
  public static function disable(): void
  {
    self::$enabled = false;
  }

  /**
   * Iniciar profile
   */
  public static function start(string $name): void
  {
    if (!self::$enabled) {
      return;
    }

    self::$profiles[$name] = [
      'start_time' => microtime(true),
      'start_memory' => memory_get_usage(true),
      'queries_before' => MetricsCollector::getMetrics()['queries_executed'] ?? 0
    ];
  }

  /**
   * Finalizar profile
   */
  public static function end(string $name): array
  {
    if (!self::$enabled || !isset(self::$profiles[$name])) {
      return [];
    }

    $start = self::$profiles[$name];
    $endTime = microtime(true);
    $endMemory = memory_get_usage(true);
    $queriesAfter = MetricsCollector::getMetrics()['queries_executed'] ?? 0;

    $profile = [
      'name' => $name,
      'duration_ms' => round(($endTime - $start['start_time']) * 1000, 2),
      'memory_used_mb' => round(($endMemory - $start['start_memory']) / 1024 / 1024, 2),
      'queries_executed' => $queriesAfter - $start['queries_before'],
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
   * Obter perfis ativos
   */
  public static function getActiveProfiles(): array
  {
    return array_keys(self::$profiles);
  }

  /**
   * Profile automático para closures
   */
  public static function profile(string $name, callable $callback)
  {
    self::start($name);

    try {
      $result = $callback();
      return $result;
    } finally {
      $profile = self::end($name);

      if (!empty($profile) && $profile['duration_ms'] > 100) {
        error_log("Performance Profile: {$name} took {$profile['duration_ms']}ms");
      }
    }
  }
}
