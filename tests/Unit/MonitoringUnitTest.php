<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Unit;

use CAFernandes\ExpressPHP\CycleORM\Monitoring\MetricsCollector;
use CAFernandes\ExpressPHP\CycleORM\Monitoring\PerformanceProfiler;
use CAFernandes\ExpressPHP\CycleORM\Monitoring\QueryLogger;
use PHPUnit\Framework\TestCase;

class MonitoringUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset metrics before each test
        MetricsCollector::reset();
    }

    public function testMetricsCollectorIncrement(): void
    {
        MetricsCollector::increment('entities_loaded', 5);
        MetricsCollector::increment('cache_hits', 3);
        MetricsCollector::increment('cache_hits', 2);
        
        $metrics = MetricsCollector::getMetrics();
        
        $this->assertEquals(5, $metrics['entities_loaded']);
        $this->assertEquals(5, $metrics['cache_hits']); // 3 + 2
    }

    public function testMetricsCollectorAddTime(): void
    {
        MetricsCollector::addTime('total_query_time', 150.5);
        MetricsCollector::addTime('total_query_time', 75.3);
        
        $metrics = MetricsCollector::getMetrics();
        
        $this->assertEquals(225.8, $metrics['total_query_time']);
    }

    public function testQueryTimeRecording(): void
    {
        // Record normal query
        MetricsCollector::recordQueryTime('SELECT * FROM users', 50.0);
        
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(1, $metrics['queries_executed']);
        $this->assertEquals(50.0, $metrics['total_query_time']);
        $this->assertEquals(0, $metrics['slow_queries']);
        
        // Record slow query (> 100ms)
        MetricsCollector::recordQueryTime('SELECT * FROM posts WHERE content LIKE "%test%"', 150.0);
        
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(2, $metrics['queries_executed']);
        $this->assertEquals(200.0, $metrics['total_query_time']);
        $this->assertEquals(1, $metrics['slow_queries']);
        
        // Check slow queries
        $slowQueries = MetricsCollector::getSlowQueries();
        $this->assertCount(1, $slowQueries);
        $this->assertStringContainsString('SELECT * FROM posts', $slowQueries[0]['query']);
        $this->assertEquals(150.0, $slowQueries[0]['time_ms']);
        $this->assertIsInt($slowQueries[0]['timestamp']);
    }

    public function testPerformanceProfiler(): void
    {
        $profiler = new PerformanceProfiler();
        
        // Start profiling
        $profiler->startTiming('test_operation');
        
        // Simulate some work
        usleep(10000); // 10ms
        
        // Stop profiling
        $elapsed = $profiler->stop('test_operation');
        
        $this->assertIsFloat($elapsed);
        $this->assertGreaterThan(5.0, $elapsed); // Should be at least 5ms
        $this->assertLessThan(100.0, $elapsed); // Allow more time in case of system load
        
        // Get profile data
        $profiles = $profiler->getProfiles();
        $this->assertArrayHasKey('test_operation', $profiles);
        $this->assertEquals($elapsed, $profiles['test_operation']);
    }

    public function testQueryLogger(): void
    {
        $logger = new QueryLogger();
        
        // Log some queries
        $logger->log('SELECT * FROM users', 25.5);
        $logger->log('INSERT INTO posts VALUES (?)', 15.2);
        $logger->log('UPDATE users SET name = ?', 8.7);
        
        $logs = $logger->getLogs();
        
        $this->assertCount(3, $logs);
        
        // Check first log entry
        $firstLog = $logs[0];
        $this->assertEquals('SELECT * FROM users', $firstLog['query']);
        $this->assertEquals(25.5, $firstLog['time_ms']);
        $this->assertIsInt($firstLog['timestamp']);
    }

    public function testQueryLoggerTruncatesLongQueries(): void
    {
        $logger = new QueryLogger();
        
        // Create a very long query
        $longQuery = str_repeat('SELECT * FROM table_with_very_long_name ', 20);
        $logger->log($longQuery, 10.0);
        
        $logs = $logger->getLogs();
        $loggedQuery = $logs[0]['query'];
        
        $this->assertLessThanOrEqual(255, strlen($loggedQuery));
        $this->assertStringEndsWith('...', $loggedQuery);
    }

    public function testSlowQueryLimit(): void
    {
        // Record 15 slow queries (limit is 10)
        for ($i = 1; $i <= 15; $i++) {
            MetricsCollector::recordQueryTime("SELECT $i", 200.0);
        }
        
        $slowQueries = MetricsCollector::getSlowQueries();
        
        // Should only keep the last 10
        $this->assertCount(10, $slowQueries);
        $this->assertStringContainsString('SELECT 15', $slowQueries[9]['query']);
        $this->assertStringContainsString('SELECT 6', $slowQueries[0]['query']);
    }

    public function testMetricsReset(): void
    {
        // Add some metrics
        MetricsCollector::increment('entities_loaded', 10);
        MetricsCollector::recordQueryTime('SELECT 1', 150.0);
        
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(10, $metrics['entities_loaded']);
        $this->assertEquals(1, $metrics['slow_queries']);
        
        // Reset
        MetricsCollector::reset();
        
        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(0, $metrics['entities_loaded']);
        $this->assertEquals(0, $metrics['slow_queries']);
        
        $slowQueries = MetricsCollector::getSlowQueries();
        $this->assertEmpty($slowQueries);
    }

    public function testQueryLoggerLimit(): void
    {
        $logger = new QueryLogger();
        
        // Log more than the limit (100 queries)
        for ($i = 1; $i <= 150; $i++) {
            $logger->log("SELECT $i", 10.0);
        }
        
        $logs = $logger->getLogs();
        
        // Should only keep the last 100
        $this->assertCount(100, $logs);
        
        // First log should be query 51, last should be query 150
        $this->assertStringContainsString('SELECT 51', $logs[0]['query']);
        $this->assertStringContainsString('SELECT 150', $logs[99]['query']);
    }
}