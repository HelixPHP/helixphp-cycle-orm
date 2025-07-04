<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Exceptions;

use CAFernandes\ExpressPHP\CycleORM\Exceptions\EntityNotFoundException;
use PHPUnit\Framework\TestCase;

class EntityNotFoundExceptionTest extends TestCase
{
    public function testExceptionStoresClassAndIdentifier(): void
    {
        $ex = new EntityNotFoundException('User', 42);
        $this->assertEquals('User', $ex->getEntityClass());
        $this->assertEquals(42, $ex->getIdentifier());
        $this->assertStringContainsString('not found', $ex->getMessage());
    }
}
