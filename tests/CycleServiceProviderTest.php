<?php
namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use Express\Core\Application;
use Cycle\ORM\ORM;
use Cycle\ORM\EntityManager;
use Cycle\Database\DatabaseManager;

class CycleServiceProviderTest extends TestCase
{
    private Application $app;
    private CycleServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock da aplicação Express-PHP
        $this->app = $this->createMock(Application::class);

        // Configurar mocks básicos
        $this->app->method('config')
            ->willReturnCallback(function($key, $default = null) {
                $config = [
                    'cycle.database' => [
                        'default' => 'sqlite',
                        'databases' => ['default' => ['connection' => 'sqlite']],
                        'connections' => [
                            'sqlite' => [
                                'driver' => 'sqlite',
                                'database' => ':memory:'
                            ]
                        ]
                    ],
                    'cycle.entities' => [
                        'directories' => [__DIR__ . '/Fixtures/Models'],
                        'namespace' => 'Tests\\Fixtures\\Models'
                    ],
                    'cycle.schema' => [
                        'cache' => false,
                        'auto_sync' => false
                    ]
                ];
                return $config[$key] ?? $default;
            });

        $this->app->method('has')->willReturn(false);
        $this->app->method('singleton')->willReturn(true);
        $this->app->method('alias')->willReturn(true);
        $this->app->method('make')->willReturnCallback(function($abstract) {
            return new \stdClass();
        });

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
