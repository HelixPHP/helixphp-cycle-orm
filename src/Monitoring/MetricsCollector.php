<?php

namespace CAFernandes\ExpressPHP\CycleORM\Monitoring;

/**
 * Coletor de métricas para Cycle ORM.
 */
class MetricsCollector
{
    /**
     * @var array<string, float|int>
     */
    private static array $metrics = [
        'queries_executed' => 0,
        'queries_failed' => 0,
        'total_query_time' => 0.0,
        'entities_persisted' => 0,
        'entities_loaded' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'slow_queries' => 0,
    ];

    /**
     * @var array<int, array{query: string, time_ms: float, timestamp: int}>
     */
    private static array $slowQueries = [];

    /**
     * Incrementar contador de métrica.
     */
    public static function increment(string $metric, int $value = 1): void
    {
        if (isset(self::$metrics[$metric])) {
            self::$metrics[$metric] += $value;
        }
    }

    /**
     * Adicionar tempo a uma métrica.
     */
    public static function addTime(string $metric, float $timeMs): void
    {
        if (isset(self::$metrics[$metric])) {
            self::$metrics[$metric] += $timeMs;
        }
    }

    /**
     * Registrar tempo de query.
     */
    public static function recordQueryTime(string $query, float $timeMs): void
    {
        self::$metrics['queries_executed']++;
        self::$metrics['total_query_time'] += $timeMs;

        // Query lenta (>100ms)
        if ($timeMs > 100) {
            self::$metrics['slow_queries']++;
            self::$slowQueries[] = [
                'query' => substr($query, 0, 100) . '...',
                'time_ms' => $timeMs,
                'timestamp' => time(),
            ];

            // Manter apenas últimas 10 queries lentas
            if (count(self::$slowQueries) > 10) {
                array_shift(self::$slowQueries);
            }
        }
    }

    /**
     * Registrar falha de query.
     */
    public static function recordQueryFailure(string $query): void
    {
        self::$metrics['queries_failed']++;
        error_log('Cycle ORM Query Failed: Query: ' . substr($query, 0, 100));
    }

    /**
     * Retorna métricas atuais (exceto slowQueries).
     *
     * @return array<string, float|int>
     */
    public static function getMetrics(): array
    {
        return self::$metrics;
        // Não existe a chave 'slowQueries' em metrics, então não é necessário unset
    }

    /**
     * Retorna queries lentas.
     *
     * @return array<int, array{query: string, time_ms: float, timestamp: int}>
     */
    public static function getSlowQueries(): array
    {
        return self::$slowQueries;
    }

    /**
     * Reiniciar métricas (usado em testes unitários).
     */
    public static function reset(): void
    {
        self::$metrics = [
            'queries_executed' => 0,
            'queries_failed' => 0,
            'total_query_time' => 0.0,
            'entities_persisted' => 0,
            'entities_loaded' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'slow_queries' => 0,
        ];
        self::$slowQueries = [];
    }
}
