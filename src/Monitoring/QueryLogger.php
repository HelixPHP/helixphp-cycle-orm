<?php

namespace Helix\CycleORM\Monitoring;

/**
 * Logger de queries para Cycle ORM.
 */
class QueryLogger
{
    /**
     * @var array<int, array{query: string, time_ms: float, timestamp: int}>
     */
    private array $logs = [];

    /**
     * Limite máximo de logs mantidos em memória.
     */
    private int $maxLogs = 100;

    /**
     * Registrar uma query.
     */
    public function log(string $query, float $timeMs): void
    {
        $this->logs[] = [
            'query' => $this->truncateQuery($query),
            'time_ms' => $timeMs,
            'timestamp' => time(),
        ];

        // Manter apenas os últimos logs
        if (count($this->logs) > $this->maxLogs) {
            array_shift($this->logs);
        }
    }

    /**
     * Retornar todos os logs.
     *
     * @return array<int, array{query: string, time_ms: float, timestamp: int}>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Limpar logs.
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }

    /**
     * Definir limite máximo de logs.
     */
    public function setMaxLogs(int $maxLogs): void
    {
        $this->maxLogs = $maxLogs;
    }

    /**
     * Resetar logs (alias para clearLogs, para compatibilidade).
     */
    public function reset(): void
    {
        $this->clearLogs();
    }

    /**
     * Limpar logs (alias para compatibilidade com versões anteriores).
     * @deprecated Use clearLogs() instead
     */
    public function clear(): void
    {
        $this->clearLogs();
    }

    /**
     * Truncar query longa.
     */
    private function truncateQuery(string $query): string
    {
        if (strlen($query) > 255) {
            return substr($query, 0, 252) . '...';
        }

        return $query;
    }
}
