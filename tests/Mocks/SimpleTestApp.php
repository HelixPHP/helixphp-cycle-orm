<?php

namespace PivotPHP\CycleORM\Tests\Mocks;

class SimpleTestApp
{
    private MockContainer $container;

    public function __construct()
    {
        $this->container = new MockContainer();

        // Bind config service
        $this->container->bind(
            'config',
            function () {
                return new class() {
                    public function get(string $key, mixed $default = null): mixed
                    {
                        return match ($key) {
                            'app.debug' => true,
                            'app.env' => 'testing',
                            default => $default
                        };
                    }
                };
            }
        );
    }

    public function getContainer(): MockContainer
    {
        return $this->container;
    }

    public function use(mixed $middleware): self
    {
        // Mock middleware registration
        return $this;
    }

    public function getConfig(): object
    {
        return $this->container->get('config');
    }
}
