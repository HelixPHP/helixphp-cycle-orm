<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use CAFernandes\ExpressPHP\CycleORM\Middleware\CycleMiddleware;
use CAFernandes\ExpressPHP\CycleORM\Middleware\TransactionMiddleware;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class MiddlewareTest extends TestCase
{
    private Application $app;

    private Request $request;

    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application();
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
    }

    public function testCycleMiddlewareInjectsServices(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => ['connection' => 'sqlite'],
                    ],
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                ]
            )
        );
        // @phpstan-ignore-next-line
        $factory = new Factory($dbal);
        $schema = new Schema([]);
        $orm = new ORM($factory, $schema);
        $em = new EntityManager($orm);
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
        $middleware->handle(
            $this->request,
            $this->response,
            function () {
            }
        );
    }

    public function testTransactionMiddlewareHandlesCommit(): void
    {
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => ['connection' => 'sqlite'],
                    ],
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                ]
            )
        );
        // @phpstan-ignore-next-line
        $factory = new Factory($dbal);
        $schema = new Schema([]);
        $orm = new ORM($factory, $schema);
        $em = new EntityManager($orm);
        $this->app = $this->createRealAppWithEM($em, $orm, $dbal);
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
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => ['connection' => 'sqlite'],
                    ],
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                ]
            )
        );
        // @phpstan-ignore-next-line
        $factory = new Factory($dbal);
        $schema = new Schema([]);
        $orm = new ORM($factory, $schema);
        $em = new EntityManager($orm);
        $this->app = $this->createRealAppWithEM($em, $orm, $dbal);
        $middleware = new TransactionMiddleware($this->app);
        $this->expectException(\Exception::class);
        $middleware->handle(
            $this->request,
            $this->response,
            function () {
                throw new \Exception('Test exception');
            }
        );
    }

    private function createRealAppWithEM(EntityManager $em, ORM $orm, DatabaseManager $dbal): Application
    {
        $app = new Application();
        $app->singleton('cycle.em', fn () => $em);
        $app->alias('cycle.em', 'em');
        $app->singleton('cycle.orm', fn () => $orm);
        $app->alias('cycle.orm', 'orm');
        $app->singleton('cycle.database', fn () => $dbal);
        $app->alias('cycle.database', 'db');

        return $app;
    }
}
