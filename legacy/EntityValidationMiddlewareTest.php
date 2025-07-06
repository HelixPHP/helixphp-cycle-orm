<?php

namespace Helix\CycleORM\Tests\Middleware;

use Helix\CycleORM\Middleware\EntityValidationMiddleware;
use Helix\Http\Request;
use Helix\Http\Response;
use PHPUnit\Framework\TestCase;

class EntityValidationMiddlewareTest extends TestCase
{
    public function testValidateEntityReturnsErrors(): void
    {
        $middleware = new EntityValidationMiddleware();
        $entity = new class {
            public int $id; // não inicializado
            public string $name; // não inicializado
        };
        $result = $middleware->validateEntity($entity);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testHandleWrapsRequest(): void
    {
        $middleware = new EntityValidationMiddleware();
        $req = $this->createMock(Request::class);
        $res = $this->createMock(Response::class);
        $called = false;
        $next = function($cycleReq, $response) use (&$called) {
            $called = true;
            $this->assertTrue(property_exists($cycleReq, 'auth'));
        };
        $middleware->handle($req, $res, $next);
        $this->assertTrue($called);
    }
}
