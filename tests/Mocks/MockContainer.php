<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Mocks;

use Express\Core\Container;

class MockContainer extends Container
{
    private array $bindings = [];
    private array $instances = [];

    public function __construct()
    {
        // Empty constructor to bypass private constructor
    }

    public function bind(string $abstract, $concrete = null, bool $singleton = false): self
    {
        $this->bindings[$abstract] = $concrete;
        return $this;
    }

    public function get(string $id): mixed
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $concrete = $this->bindings[$id];

            if (is_callable($concrete)) {
                $instance = $concrete();
            } elseif (is_string($concrete)) {
                $instance = new $concrete();
            } else {
                $instance = $concrete;
            }

            $this->instances[$id] = $instance;
            return $instance;
        }

        throw new \Exception("Service $id not found");
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    public function singleton(string $abstract, $concrete = null): self
    {
        return $this->bind($abstract, $concrete, true);
    }
}
