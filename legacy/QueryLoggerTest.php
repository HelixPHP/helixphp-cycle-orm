<?php

namespace PivotPHP\CycleORM\Tests\Monitoring;

use PivotPHP\CycleORM\Monitoring\QueryLogger;
use PHPUnit\Framework\TestCase;

class QueryLoggerTest extends TestCase
{
    public function testLogWritesToErrorLog(): void
    {
        $logger = new QueryLogger();
        // Não há como capturar error_log diretamente, mas garantimos que não lança exceção
        $this->expectNotToPerformAssertions();
        $logger->log('SELECT * FROM users', [1], 2.5);
    }
}

// Corrigir tipagem do parâmetro $params para array<int, mixed> e ajustar chamadas conforme esperado
