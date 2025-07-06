<?php

namespace Helix\CycleORM\Tests\Monitoring;

use Helix\CycleORM\Monitoring\MetricsCollector;
use Helix\CycleORM\Monitoring\PerformanceProfiler;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function testMetricsAndProfilerTogether(): void
    {
        MetricsCollector::reset();
        PerformanceProfiler::enable();
        PerformanceProfiler::start('integration');
        MetricsCollector::increment('entities_persisted', 3);
        MetricsCollector::recordQueryTime('SELECT * FROM integration', 120.0);
        $profile = PerformanceProfiler::end('integration');
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(3, $metrics['entities_persisted']);
        $this->assertEquals(1, $metrics['slow_queries']);
        $this->assertArrayHasKey('duration_ms', $profile);
    }
}
