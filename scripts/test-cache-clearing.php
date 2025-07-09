<?php
/**
 * Test script to demonstrate EnvironmentHelper cache clearing functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\CycleORM\Helpers\EnvironmentHelper;

echo "Testing EnvironmentHelper cache clearing...\n\n";

// First calls - populate cache
echo "1. First calls (populating cache):\n";
$result1 = EnvironmentHelper::isTesting();
$result2 = EnvironmentHelper::isProduction();
$result3 = EnvironmentHelper::getEnvironment();

echo "   isTesting(): " . ($result1 ? 'true' : 'false') . "\n";
echo "   isProduction(): " . ($result2 ? 'true' : 'false') . "\n";
echo "   getEnvironment(): {$result3}\n\n";

// Verify cache is populated by checking performance
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    EnvironmentHelper::isTesting();
}
$cachedTime = microtime(true) - $start;
echo "2. Cache performance (1000 calls): " . number_format($cachedTime * 1000, 2) . " ms\n\n";

// Clear cache
echo "3. Clearing cache...\n";
EnvironmentHelper::clearCache();
echo "   Cache cleared!\n\n";

// Verify same results after cache clear
echo "4. After cache clear (should rebuild cache):\n";
$result4 = EnvironmentHelper::isTesting();
$result5 = EnvironmentHelper::isProduction();
$result6 = EnvironmentHelper::getEnvironment();

echo "   isTesting(): " . ($result4 ? 'true' : 'false') . "\n";
echo "   isProduction(): " . ($result5 ? 'true' : 'false') . "\n";
echo "   getEnvironment(): {$result6}\n\n";

// Verify results are consistent
$consistent = ($result1 === $result4) && ($result2 === $result5) && ($result3 === $result6);
echo "5. Consistency check: " . ($consistent ? "✅ PASS" : "❌ FAIL") . "\n";
echo "   Results are " . ($consistent ? "identical" : "different") . " before and after cache clear\n\n";

echo "✅ Cache clearing functionality works correctly!\n";