<?php

namespace PivotPHP\CycleORM\Tests\Mocks;

use PivotPHP\Core\Core\Application;

class MockApplication extends Application
{
    public function __construct()
    {
        // Não inicializa $container aqui!
    }

    public function singleton(string $abstract, mixed $concrete = null): Application
    {
        $this->container->singleton($abstract, $concrete);

        return $this;
    }

    public function booted(?callable $callback = null): void
    {
        // Mock implementation - não faz nada
    }

    public function alias(string $alias, string $abstract): Application
    {
        // Mock implementation
        return $this;
    }

    public function make(string $abstract): mixed
    {
        // Mock implementation - retorna um objeto genérico
        return new \stdClass();
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        $config = [
            'cycle.database' => [
                'default' => 'sqlite',
                'databases' => ['default' => ['connection' => 'sqlite']],
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:',
                    ],
                ],
            ],
            'cycle.entities' => [
                'directories' => [__DIR__ . '/../Fixtures/Models'],
                'namespace' => 'Tests\Fixtures\Models',
            ],
            'cycle.schema' => [
                'cache' => false,
                'auto_sync' => false,
            ],
        ];

        return $config[$key] ?? $default;
    }

    public function has(string $service): bool
    {
        // Para testes, retorna true para serviços conhecidos, false para outros
        $known = [
            'cycle.orm',
            'cycle.em',
            'cycle.database',
            'cycle.schema',
            'db',
            'orm',
            'em',
            'schema',
        ];

        return in_array($service, $known, true);
    }
}
