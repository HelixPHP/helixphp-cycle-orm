<?php

namespace CAFernandes\ExpressPHP\CycleORM\Monitoring;

/**
 * Logger básico de queries para Cycle ORM
 * (Exemplo: apenas loga queries no error_log)
 */
class QueryLogger
{
  /**
   * Loga uma query do Cycle ORM
   *
   * @param string $query
   * @param array<int, mixed> $params
   * @param float $timeMs
   * @return void
   */
    public function log(string $query, array $params = [], float $timeMs = 0.0): void
    {
        $msg = sprintf(
            '[Cycle Query] %s | Params: %s | Time: %.2fms',
            $query,
            json_encode($params),
            $timeMs
        );
        error_log($msg);
    }
}
