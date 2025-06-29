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
    private Application $app;
    private Request $request;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->createMock(Application::class);
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
    }

    public function testCycleMiddlewareInjectsServices(): void
    {
        // Mock ORM services
        $orm = $this->createMock(\Cycle\ORM\ORM::class);
        $em = $this->createMock(\Cycle\ORM\EntityManager::class);
        $db = $this->createMock(\Cycle\Database\DatabaseManager::class);

        $this->app->method('has')->willReturn(true);
        $this->app->method('make')->willReturnMap([
            ['cycle.orm', $orm],
            ['cycle.em', $em],
            ['cycle.database', $db]
        ]);

        $middleware = new CycleMiddleware($this->app);

        $called = false;
        $next = function() use (&$called) {
            $called = true;
        };

        $middleware->handle($this->request, $this->response, $next);

        $this->assertTrue($called);
    }

    public function testCycleMiddlewareThrowsWhenORMNotRegistered(): void
    {
        $this->app->method('has')->willReturn(false);

        $middleware = new CycleMiddleware($this->app);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cycle ORM not properly registered');

        $middleware->handle($this->request, $this->response, function() {});
    }

    public function testTransactionMiddlewareHandlesCommit(): void
    {
        $em = $this->createMock(\Cycle\ORM\EntityManager::class);
        $em->method('hasChanges')->willReturn(true);
        $em->expects($this->once())->method('run');

        $this->app->method('has')->willReturn(true);
        $this->app->method('make')->willReturn($em);

        $middleware = new TransactionMiddleware($this->app);

        $called = false;
        $next = function() use (&$called) {
            $called = true;
        };

        $middleware->handle($this->request, $this->response, $next);

        $this->assertTrue($called);
    }

    public function testTransactionMiddlewareHandlesRollback(): void
    {
        $em = $this->createMock(\Cycle\ORM\EntityManager::class);
        $em->expects($this->once())->method('clean');

        $this->app->method('has')->willReturn(true);
        $this->app->method('make')->willReturn($em);

        $middleware = new TransactionMiddleware($this->app);

        $this->expectException(\Exception::class);

        $middleware->handle($this->request, $this->response, function() {
            throw new \Exception('Test exception');
        });
    }
}