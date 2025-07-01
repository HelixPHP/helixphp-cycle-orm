<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Mocks;

class MockApplication extends \Express\Core\Application
{
    public function __construct()
    {
      // Não inicializa $container aqui!
    }

    public function singleton(...$args)
    {
        return $this->container->singleton(...$args);
    }
    public function booted()
    {
        return $this->container->booted();
    }
    public function alias(...$args)
    {
        return $this->container->alias(...$args);
    }
    public function make(...$args)
    {
        return $this->container->make(...$args);
    }

    public function config($key, $default = null)
    {
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
                'directories' => [__DIR__ . '/../Fixtures/Models'],
                'namespace' => 'Tests\\Fixtures\\Models'
            ],
            'cycle.schema' => [
                'cache' => false,
                'auto_sync' => false
            ]
        ];
        return $config[$key] ?? $default;
    }

    public function has($service)
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
            'schema'
        ];
        return in_array($service, $known, true);
    }
}
