<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Middleware\CycleMiddleware;
use CAFernandes\ExpressPHP\CycleORM\Middleware\TransactionMiddleware;
use Express\Http\Request;
use Express\Http\Response;
use Express\Core\Application;

class MiddlewareTest extends TestCase
{
  private $app;
  private $request;
  private $response;

  protected function setUp(): void
  {
    parent::setUp();
    $this->app = new Application();
    $this->request = $this->createMock(Request::class);
    $this->response = $this->createMock(Response::class);
  }

  public function testCycleMiddlewareInjectsServices(): void
  {
    $dbal = new \Cycle\Database\DatabaseManager(new \Cycle\Database\Config\DatabaseConfig([
      'default' => 'default',
      'databases' => [
        'default' => ['connection' => 'sqlite']
      ],
      'connections' => [
        'sqlite' => [
          'driver' => 'sqlite',
          'database' => ':memory:'
        ]
      ]
    ]));
    $factory = new \Cycle\ORM\Factory($dbal);
    $schema = new \Cycle\ORM\Schema([]);
    $orm = new \Cycle\ORM\ORM($factory, $schema);
    $em = new \Cycle\ORM\EntityManager($orm);
    $this->app->singleton('cycle.orm', $orm);
    $this->app->singleton('cycle.em', $em);
    $this->app->singleton('cycle.database', $dbal);
    $middleware = new CycleMiddleware($this->app);
    $called = false;
    $next = function () use (&$called) {
      $called = true;
    };
    $middleware->handle($this->request, $this->response, $next);
    $this->assertTrue($called);
  }

  public function testCycleMiddlewareThrowsWhenORMNotRegistered(): void
  {
    $middleware = new CycleMiddleware($this->app);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Cycle ORM not properly registered');
    $middleware->handle($this->request, $this->response, function () {});
  }

  public function testTransactionMiddlewareHandlesCommit(): void
  {
    $dbal = new \Cycle\Database\DatabaseManager(new \Cycle\Database\Config\DatabaseConfig([
      'default' => 'default',
      'databases' => [
        'default' => ['connection' => 'sqlite']
      ],
      'connections' => [
        'sqlite' => [
          'driver' => 'sqlite',
          'database' => ':memory:'
        ]
      ]
    ]));
    $factory = new \Cycle\ORM\Factory($dbal);
    $schema = new \Cycle\ORM\Schema([]);
    $orm = new \Cycle\ORM\ORM($factory, $schema);
    $em = new \Cycle\ORM\EntityManager($orm);
    $this->app = $this->createRealAppWithEM($em);
    $middleware = new TransactionMiddleware($this->app);
    $called = false;
    $next = function () use (&$called) {
      $called = true;
    };
    $middleware->handle($this->request, $this->response, $next);
    $this->assertTrue($called);
  }

  public function testTransactionMiddlewareHandlesRollback(): void
  {
    $dbal = new \Cycle\Database\DatabaseManager(new \Cycle\Database\Config\DatabaseConfig([
      'default' => 'default',
      'databases' => [
        'default' => ['connection' => 'sqlite']
      ],
      'connections' => [
        'sqlite' => [
          'driver' => 'sqlite',
          'database' => ':memory:'
        ]
      ]
    ]));
    $factory = new \Cycle\ORM\Factory($dbal);
    $schema = new \Cycle\ORM\Schema([]);
    $orm = new \Cycle\ORM\ORM($factory, $schema);
    $em = new \Cycle\ORM\EntityManager($orm);
    $this->app = $this->createRealAppWithEM($em);
    $middleware = new TransactionMiddleware($this->app);
    $this->expectException(\Exception::class);
    $middleware->handle($this->request, $this->response, function () {
      throw new \Exception('Test exception');
    });
  }

  private function createRealAppWithEM($em)
  {
    $app = new \Express\Core\Application();
    $app->singleton('cycle.em', fn() => $em);
    $app->alias('cycle.em', 'em');
    $app->singleton('cycle.orm', fn() => $em->getORM());
    $app->alias('cycle.orm', 'orm');
    $app->singleton('cycle.database', fn() => $em->getORM()->getFactory()->getDatabaseProvider());
    $app->alias('cycle.database', 'db');
    return $app;
  }
}
