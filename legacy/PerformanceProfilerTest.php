<?php

namespace Helix\CycleORM\Tests\Monitoring;

use Helix\CycleORM\Monitoring\PerformanceProfiler;
use Helix\CycleORM\Monitoring\MetricsCollector;
use PHPUnit\Framework\TestCase;

class PerformanceProfilerTest extends TestCase
{
    protected function setUp(): void
    {
        PerformanceProfiler::disable();
        MetricsCollector::reset();
    }

    public function testEnableDisable(): void
    {
        PerformanceProfiler::enable();
        $this->assertTrue(PerformanceProfiler::isEnabled());
        PerformanceProfiler::disable();
        $this->assertFalse(PerformanceProfiler::isEnabled());
    }

    public function testStartAndEndProfile(): void
    {
        PerformanceProfiler::enable();
        PerformanceProfiler::start('test');
        usleep(1000);
        $profile = PerformanceProfiler::end('test');
        $this->assertArrayHasKey('name', $profile);
        $this->assertArrayHasKey('duration_ms', $profile);
        $this->assertArrayHasKey('memory_used_mb', $profile);
        $this->assertArrayHasKey('timestamp', $profile);
    }
}
