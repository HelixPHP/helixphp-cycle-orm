<?php
namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;

class CycleServiceProviderTest extends TestCase
{
    private $app;
    private CycleServiceProvider $provider;

    protected function setUp(): void
    {
        // Mock da aplicação Express-PHP
        $this->app = $this->createMock('Express\\Core\\Application');

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
                    ]
                ];
                return $config[$key] ?? $default;
            });

        $this->app->method('has')->willReturn(false);
        $this->app->method('singleton')->willReturn(true);
        $this->app->method('alias')->willReturn(true);

        $this->provider = new CycleServiceProvider($this->app);
    }

    public function testServiceProviderRegistersServices(): void
    {
        // Verificar se o provider pode ser instanciado
        $this->assertInstanceOf(CycleServiceProvider::class, $this->provider);

        // Testar método register
        $this->assertNull($this->provider->register());

        // Testar método boot
        $this->assertNull($this->provider->boot());
    }

    public function testDatabaseConfigValidation(): void
    {
        // Testar configuração inválida
        $invalidConfig = [];

        $this->expectException(\InvalidArgumentException::class);

        // Simular validação de config (seria chamada internamente)
        $reflection = new \ReflectionClass($this->provider);
        $method = $reflection->getMethod('validateDatabaseConfig');
        $method->setAccessible(true);
        $method->invoke($this->provider, $invalidConfig);
    }
}