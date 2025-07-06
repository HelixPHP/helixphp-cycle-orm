<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Middleware;

use CAFernandes\ExpressPHP\CycleORM\Middleware\EntityValidationMiddleware;
use Express\Http\Request;
use Express\Http\Response;
use PHPUnit\Framework\TestCase;

class EntityValidationMiddlewareExtraTest extends TestCase
{
    public function testValidateEntityWithValidEntity(): void
    {
        $middleware = new EntityValidationMiddleware();
        $entity = new class {
            public int $id = 1;
            public string $name = 'ok';
        };
        $result = $middleware->validateEntity($entity);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
}
