<?php

namespace PivotPHP\CycleORM\Tests\Support;

use PivotPHP\CycleORM\Tests\Mocks\MockContainer;

/**
 * Simple test application that mimics PivotPHP\Core\Application interface
 * without complex inheritance issues.
 */
class TestApplication
{
    private MockContainer $container;

    public function __construct()
    {
        $this->container = new MockContainer();
        $this->setupDefaultBindings();
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

    private function setupDefaultBindings(): void
    {
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
}
