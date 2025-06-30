<?php
namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use Express\Core\Application;

/**
 * @covers \CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider
 */
class CycleServiceProviderTest extends TestCase
{
    private $container;
    private $app;
    private CycleServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new class extends Application {
            private $bootedCallbacks = [];
            public function booted($callback = null) {
                if ($callback) {
                    $this->bootedCallbacks[] = $callback;
                } else {
                    foreach ($this->bootedCallbacks as $cb) {
                        $cb($this);
                    }
                }
            }
        };
        $ref = new \ReflectionObject($this->app);
        $prop = $ref->getProperty('container');
        $prop->setAccessible(true);
        $this->container = $prop->getValue($this->app);
        $this->provider = new CycleServiceProvider($this->app);
    }

    public function testServiceProviderCanBeInstantiated(): void
    {
        $this->assertInstanceOf(CycleServiceProvider::class, $this->provider);
    }

    public function testRegisterMethodDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        $this->provider->register();
    }

    public function testBootMethodDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();
        $this->provider->boot();
    }

    public function testDatabaseConfigValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required database config key');

        // Simular validação de config inválida
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('validateDatabaseConfig');
        $method->setAccessible(true);
        $method->invoke($this->provider, []);
    }

    public function testEntityConfigValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one entity directory must be configured');

        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('validateEntityConfig');
        $method->setAccessible(true);
        $method->invoke($this->provider, []);
    }
}
