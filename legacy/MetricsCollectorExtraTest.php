<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Monitoring;

use CAFernandes\ExpressPHP\CycleORM\Monitoring\MetricsCollector;
use PHPUnit\Framework\TestCase;

class MetricsCollectorExtraTest extends TestCase
{
    protected function setUp(): void
    {
        MetricsCollector::reset();
    }

    public function testRecordQueryFailureIncrementsAndLogs(): void
    {
        MetricsCollector::recordQueryFailure('SELECT * FROM fail');
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(1, $metrics['queries_failed']);
    }

    public function testSlowQueriesLimit(): void
    {
        for ($i = 0; $i < 15; $i++) {
            MetricsCollector::recordQueryTime('SELECT ' . $i, 200.0);
        }
        $slow = MetricsCollector::getSlowQueries();
        $this->assertCount(10, $slow, 'Deve manter apenas as 10 últimas queries lentas');
        $this->assertStringContainsString('SELECT 14', $slow[9]['query']);
    }
}
