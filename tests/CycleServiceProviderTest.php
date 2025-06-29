<?php

// ============================================================================
// TESTES UNITÁRIOS - tests/CycleServiceProviderTest.php
// ============================================================================

namespace ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use Express\Core\Application;
use ExpressPHP\CycleORM\CycleServiceProvider;

class CycleServiceProviderTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->config(['cycle' => [
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:'
                    ]
                ]
            ]
        ]]);

        $provider = new CycleServiceProvider($this->app);
        $provider->register();
        $provider->boot();
    }

    public function testDatabaseManagerIsRegistered(): void
    {
        $this->assertTrue($this->app->has('cycle.database'));
        $this->assertInstanceOf(
            \Cycle\Database\DatabaseManager::class,
            $this->app->make('cycle.database')
        );
    }

    public function testORMIsRegistered(): void
    {
        $this->assertTrue($this->app->has('cycle.orm'));
        $this->assertInstanceOf(
            \Cycle\ORM\ORM::class,
            $this->app->make('cycle.orm')
        );
    }

    public function testEntityManagerIsRegistered(): void
    {
        $this->assertTrue($this->app->has('cycle.em'));
        $this->assertInstanceOf(
            \Cycle\ORM\EntityManager::class,
            $this->app->make('cycle.em')
        );
    }

    public function testAliasesWork(): void
    {
        $this->assertTrue($this->app->has('db'));
        $this->assertTrue($this->app->has('orm'));
        $this->assertTrue($this->app->has('em'));

        $this->assertSame(
            $this->app->make('cycle.database'),
            $this->app->make('db')
        );
    }
}