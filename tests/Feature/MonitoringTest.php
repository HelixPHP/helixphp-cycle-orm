<?php

namespace PivotPHP\CycleORM\Tests\Feature;

use PivotPHP\Core\Core\Application;
use PivotPHP\CycleORM\CycleServiceProvider;
use PivotPHP\CycleORM\Monitoring\MetricsCollector;
use PivotPHP\CycleORM\Monitoring\PerformanceProfiler;
use PivotPHP\CycleORM\Monitoring\QueryLogger;
use PivotPHP\CycleORM\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class MonitoringTest extends TestCase
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

    public function testSlowQueryLimit(): void
    {
        // Record 15 slow queries (limit is 10)
        for ($i = 1; $i <= 15; $i++) {
            MetricsCollector::recordQueryTime("SELECT {$i}", 200.0);
        }

        $slowQueries = MetricsCollector::getSlowQueries();

        // Should only keep the last 10
        $this->assertCount(10, $slowQueries);
        $this->assertStringContainsString('SELECT 15', $slowQueries[9]['query']);
        $this->assertStringContainsString('SELECT 6', $slowQueries[0]['query']);
    }

    public function testQueryFailureRecording(): void
    {
        MetricsCollector::recordQueryFailure('INVALID SQL QUERY');

        $metrics = MetricsCollector::getMetrics();
        $this->assertEquals(1, $metrics['queries_failed']);
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
        $this->assertLessThan(50.0, $elapsed); // Should be less than 50ms

        // Get profile data
        $profiles = $profiler->getProfiles();
        $this->assertArrayHasKey('test_operation', $profiles);
        $this->assertEquals($elapsed, $profiles['test_operation']);
    }

    public function testPerformanceProfilerMultipleOperations(): void
    {
        $profiler = new PerformanceProfiler();

        // Profile multiple operations
        $profiler->startTiming('operation1');
        usleep(5000);
        $elapsed1 = $profiler->stop('operation1');

        $profiler->startTiming('operation2');
        usleep(8000);
        $elapsed2 = $profiler->stop('operation2');

        $profiles = $profiler->getProfiles();
        $this->assertCount(2, $profiles);
        $this->assertEquals($elapsed1, $profiles['operation1']);
        $this->assertEquals($elapsed2, $profiles['operation2']);

        // Test reset
        $profiler->reset();
        $profiles = $profiler->getProfiles();
        $this->assertEmpty($profiles);
    }

    public function testPerformanceProfilerStopNonExistentOperation(): void
    {
        $profiler = new PerformanceProfiler();

        $elapsed = $profiler->stop('non_existent');
        $this->assertEquals(0.0, $elapsed);
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

        // Check query was truncated if too long
        $longQuery = str_repeat('SELECT * FROM table_with_very_long_name ', 20);
        $logger->log($longQuery, 10.0);

        $logs = $logger->getLogs();
        $lastLog = end($logs);
        $this->assertLessThanOrEqual(255, strlen($lastLog['query']));
        $this->assertStringEndsWith('...', $lastLog['query']);
    }

    public function testQueryLoggerLimit(): void
    {
        $logger = new QueryLogger();

        // Log more than the limit (100 queries)
        for ($i = 1; $i <= 150; $i++) {
            $logger->log("SELECT {$i}", 10.0);
        }

        $logs = $logger->getLogs();

        // Should only keep the last 100
        $this->assertCount(100, $logs);

        // First log should be query 51, last should be query 150
        $this->assertStringContainsString('SELECT 51', $logs[0]['query']);
        $this->assertStringContainsString('SELECT 150', $logs[99]['query']);
    }

    public function testQueryLoggerReset(): void
    {
        $logger = new QueryLogger();

        $logger->log('SELECT 1', 10.0);
        $logger->log('SELECT 2', 20.0);

        $this->assertCount(2, $logger->getLogs());

        $logger->reset();
        $this->assertEmpty($logger->getLogs());
    }

    public function testIntegrationWithServiceProvider(): void
    {
        // Set environment variables to enable monitoring
        $_ENV['CYCLE_LOG_QUERIES'] = '1';
        $_ENV['CYCLE_PROFILE_QUERIES'] = '1';

        // Setup monitoring services manually
        $this->app->getContainer()->bind('cycle.query_logger', fn () => new QueryLogger());
        $this->app->getContainer()->bind('cycle.profiler', fn () => new PerformanceProfiler());

        $container = $this->app->getContainer();

        // Should have monitoring services
        $this->assertTrue($container->has('cycle.query_logger'));
        $this->assertTrue($container->has('cycle.profiler'));

        $queryLogger = $container->get('cycle.query_logger');
        $profiler = $container->get('cycle.profiler');

        $this->assertInstanceOf(QueryLogger::class, $queryLogger);
        $this->assertInstanceOf(PerformanceProfiler::class, $profiler);

        // Cleanup
        unset($_ENV['CYCLE_LOG_QUERIES'], $_ENV['CYCLE_PROFILE_QUERIES']);
    }

    public function testMonitoringInProductionEnvironment(): void
    {
        // Set production environment
        $_ENV['APP_ENV'] = 'production';
        $_ENV['APP_DEBUG'] = '0';

        // Even with monitoring env vars, shouldn't enable in production
        $_ENV['CYCLE_LOG_QUERIES'] = '1';
        $_ENV['CYCLE_PROFILE_QUERIES'] = '1';

        // Create new provider for production environment
        $prodApp = $this->createApplication();
        $prodProvider = new CycleServiceProvider($prodApp);
        $prodProvider->boot();

        $container = $prodApp->getContainer();

        // Should NOT have monitoring services in production
        $this->assertFalse($container->has('cycle.query_logger'));
        $this->assertFalse($container->has('cycle.profiler'));

        // Cleanup
        unset($_ENV['CYCLE_LOG_QUERIES'], $_ENV['CYCLE_PROFILE_QUERIES']);
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = '1';
    }

    private function createApplication(): Application
    {
        $app = new Application();
        $container = $app->getContainer();

        $container->bind(
            'config',
            function () {
                return new class() {
                    public function get(string $key, mixed $default = null): mixed
                    {
                        return match ($key) {
                            'app.debug' => '1' === $_ENV['APP_DEBUG'],
                            'app.env' => $_ENV['APP_ENV'] ?? 'testing',
                            default => $default
                        };
                    }
                };
            }
        );

        return $app;
    }
}
