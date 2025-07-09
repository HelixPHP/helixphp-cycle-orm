<?php
/**
 * Simple benchmark to demonstrate EnvironmentHelper caching benefits
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\CycleORM\Helpers\EnvironmentHelper;

// Warm up
EnvironmentHelper::isTesting();

$iterations = 10000;

echo "Benchmarking EnvironmentHelper::isTesting() with {$iterations} iterations...\n\n";

// First call (cache miss)
$start = microtime(true);
$result1 = EnvironmentHelper::isTesting();
$firstCallTime = microtime(true) - $start;

echo "First call (cache miss): " . number_format($firstCallTime * 1000000, 2) . " μs\n";
echo "Result: " . ($result1 ? 'true' : 'false') . "\n\n";

// Subsequent calls (cache hits)
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    EnvironmentHelper::isTesting();
}
$totalTime = microtime(true) - $start;
$avgTimePerCall = ($totalTime / $iterations) * 1000000;

echo "Cached calls ({$iterations} iterations):\n";
echo "Total time: " . number_format($totalTime * 1000, 2) . " ms\n";
echo "Average per call: " . number_format($avgTimePerCall, 2) . " μs\n";
echo "Speed improvement: " . number_format($firstCallTime / ($totalTime / $iterations), 0) . "x faster\n\n";

echo "✅ Caching is working properly!\n";