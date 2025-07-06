<?php

namespace Helix\CycleORM\Tests\Middleware;

use Helix\CycleORM\Middleware\EntityValidationMiddleware;
use Helix\Http\Request;
use Helix\Http\Response;
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
