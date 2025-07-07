<?php

namespace PivotPHP\CycleORM\Tests\Monitoring;

use PivotPHP\CycleORM\Monitoring\MetricsCollector;
use PHPUnit\Framework\TestCase;

class MetricsCollectorTest extends TestCase
{
    protected function setUp(): void
    {
        MetricsCollector::reset();
    }

    public function testIncrementAndGetMetrics(): void
    {
        MetricsCollector::increment('entities_persisted', 2);
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(2, $metrics['entities_persisted']);
    }

    public function testRecordQueryTimeAndSlowQueries(): void
    {
        MetricsCollector::recordQueryTime('SELECT 1', 150.0);
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(1, $metrics['slow_queries']);
        $slow = MetricsCollector::getSlowQueries();
        $this->assertNotEmpty($slow);
        $this->assertStringContainsString('SELECT 1', $slow[0]['query']);
    }

    public function testReset(): void
    {
        MetricsCollector::increment('entities_loaded', 5);
        MetricsCollector::reset();
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(0, $metrics['entities_loaded']);
    }
}
