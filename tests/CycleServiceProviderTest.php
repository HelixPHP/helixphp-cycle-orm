<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use CAFernandes\ExpressPHP\CycleORM\Middleware\CycleMiddleware;
use Express\Core\Application;
use Express\Routing\Router;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider
 *
 * @internal
 */
class CycleServiceProviderTest extends TestCase
{
    private object $container;

    private Application $app;

    private CycleServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new class () extends Application {
            /** @var array<int, callable> */
            private array $bootedCallbacks = [];

            public function booted(?callable $callback = null): void
            {
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
        // Mock do router para garantir que get() aceite qualquer callable
        $mockRouter = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $mockRouter->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function ($route, $handler) {
                    $this->assertIsCallable($handler, 'Handler registrado no router deve ser callable');
                }
            );
        // Cria um mock do container que retorna o mockRouter ao chamar get('router')
        $mockContainer = $this->getMockBuilder(get_class($this->container))
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $mockContainer->method('get')->willReturnCallback(
            function ($service) use ($mockRouter) {
                if ('router' === $service) {
                    return $mockRouter;
                }
            }
        );
        // Injeta o mockContainer na app
        $ref = new \ReflectionObject($this->app);
        $prop = $ref->getProperty('container');
        $prop->setAccessible(true);
        $prop->setValue($this->app, $mockContainer);
        // Adiciona o middleware CycleMiddleware ao Application
        $this->app->use(new CycleMiddleware($this->app));
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
