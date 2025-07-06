<?php

namespace Helix\CycleORM\Tests\Mocks;

use Helix\Core\Application;

class MockApplication extends Application
{
    private MockContainer $mockContainer;

    public function __construct()
    {
        $this->startTime = new \DateTime();
        $this->mockContainer = new MockContainer();

        // Bind basic services that the parent would bind
        $this->mockContainer->bind(
            'config',
            function () {
                return new class () {
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
        return $this->mockContainer;
    }

    public function use(mixed $middleware): self
    {
        // Mock middleware registration
        return $this;
    }
}
