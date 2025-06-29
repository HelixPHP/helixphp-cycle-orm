<?php
namespace CAFernandes\ExpressPHP\CycleORM\Monitoring;

/**
 * Coletor de métricas para Cycle ORM
 */
class MetricsCollector
{
    private static array $metrics = [
        'queries_executed' => 0,
        'queries_failed' => 0,
        'total_query_time' => 0,
        'entities_persisted' => 0,
        'entities_loaded' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'slow_queries' => 0,
    ];

    private static array $queryTimes = [];
    private static array $slowQueries = [];

    /**
     * Incrementar contador de métrica
     */
    public static function increment(string $metric, int $value = 1): void
    {
        if (isset(self::$metrics[$metric])) {
            self::$metrics[$metric] += $value;
        }
    }

    /**
     * Registrar tempo de query
     */
    public static function recordQueryTime(string $query, float $timeMs): void
    {
        self::$metrics['queries_executed']++;
        self::$metrics['total_query_time'] += $timeMs;
        self::$queryTimes[] = $timeMs;

        // Query lenta (>100ms)
        if ($timeMs > 100) {
            self::$metrics['slow_queries']++;
            self::$slowQueries[] = [
                'query' => substr($query, 0, 100) . '...',
                'time_ms' => $timeMs,
                'timestamp' => time()
            ];

            // Manter apenas últimas 10 queries lentas
            if (count(self::$slowQueries) > 10) {
                array_shift(self::$slowQueries);
            }
        }
    }

    /**
     * Registrar falha de query
     */
    public static function recordQueryFailure(string $query, string $error): void
    {
        self::$metrics['queries_failed']++;

        // Log do erro
        error_log("Cycle ORM Query Failed: {$error} - Query: " . substr($query, 0, 100));
    }

    /**
     * Obter todas as métricas
     */
    public static function getMetrics(): array
    {
        $metrics = self::$metrics;

        // Calcular estatísticas adicionais
        if (!empty(self::$queryTimes)) {
            $metrics['avg_query_time'] = round(array_sum(self::$queryTimes) / count(self::$queryTimes), 2);
            $metrics['max_query_time'] = round(max(self::$queryTimes), 2);
            $metrics['min_query_time'] = round(min(self::$queryTimes), 2);
        }

        $metrics['slow_queries_details'] = self::$slowQueries;
        $metrics['memory_usage_mb'] = round(memory_get_usage(true) / 1024 / 1024, 2);
        $metrics['uptime_seconds'] = time() - $_SERVER['REQUEST_TIME'];

        return $metrics;
    }

    /**
     * Obter métricas no formato Prometheus
     */
    public static function getPrometheusMetrics(): string
    {
        $metrics = self::getMetrics();
        $output = [];

        foreach ($metrics as $name => $value) {
            if (is_numeric($value)) {
                $metricName = 'cycle_orm_' . $name;
                $output[] = "# TYPE {$metricName} counter";
                $output[] = "{$metricName} {$value}";
            }
        }

        return implode("\n", $output);
    }

    /**
     * Resetar métricas
     */
    public static function reset(): void
    {
        self::$metrics = array_fill_keys(array_keys(self::$metrics), 0);
        self::$queryTimes = [];
        self::$slowQueries = [];
    }
}