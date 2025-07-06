<?php

namespace Helix\CycleORM\Tests\Unit;

use Helix\CycleORM\CycleServiceProvider;
use Helix\CycleORM\Tests\Support\TestApplication;
use PHPUnit\Framework\TestCase;

class CycleServiceProviderUnitTest extends TestCase
{
    public function testServiceProviderCanBeInstantiated(): void
    {
        // This test verifies the structure without requiring full integration
        $this->assertTrue(class_exists(CycleServiceProvider::class));
        $this->assertTrue(method_exists(CycleServiceProvider::class, 'register'));
        $this->assertTrue(method_exists(CycleServiceProvider::class, 'boot'));
    }

    public function testHelperMethodsExist(): void
    {
        // Include the helper functions
        require_once __DIR__ . '/../../src/Helpers/env.php';
        require_once __DIR__ . '/../../src/Helpers/app_path.php';

        // Test that helper methods are available
        $this->assertTrue(function_exists('env'), 'env function does not exist');
        $this->assertTrue(function_exists('app_path'), 'app_path function does not exist');

        // Config function was moved to CycleServiceProvider so it's not globally available
        $this->assertFalse(function_exists('config'), 'config function should not exist as a global function');
    }

    public function testServiceProviderConstants(): void
    {
        // Test service provider class constants and structure
        $reflection = new \ReflectionClass(CycleServiceProvider::class);

        $this->assertTrue($reflection->hasMethod('register'));
        $this->assertTrue($reflection->hasMethod('boot'));
        $this->assertTrue($reflection->hasMethod('__construct'));

        // Test that the class extends the Express ServiceProvider
        $this->assertTrue($reflection->getParentClass() !== false);
        $this->assertEquals('Helix\Providers\ServiceProvider', $reflection->getParentClass()->getName());
    }

    public function testHelperFunctionsWork(): void
    {
        // Include the helper functions
        require_once __DIR__ . '/../../src/Helpers/env.php';
        require_once __DIR__ . '/../../src/Helpers/app_path.php';

        // Test helper functions work in isolation
        $_ENV['TEST_VAR'] = 'test_value';

        $this->assertEquals('test_value', \env('TEST_VAR'));
        $this->assertEquals('default', \env('NON_EXISTENT', 'default'));

        // Test app_path helper
        $path = \app_path('test');
        $this->assertIsString($path);
        $this->assertStringContainsString('test', $path);
    }
}
