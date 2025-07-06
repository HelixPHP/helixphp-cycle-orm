<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Exceptions;

use CAFernandes\ExpressPHP\CycleORM\Exceptions\EntityNotFoundException;
use CAFernandes\ExpressPHP\CycleORM\Exceptions\CycleORMException;
use PHPUnit\Framework\TestCase;

class ExceptionHandlingTest extends TestCase
{
    public function testEntityNotFoundIsCatchable(): void
    {
        try {
            throw new EntityNotFoundException('User', 99);
        } catch (EntityNotFoundException $e) {
            $this->assertEquals('User', $e->getEntityClass());
            $this->assertEquals(99, $e->getIdentifier());
        }
    }

    public function testCycleORMExceptionContext(): void
    {
        $ex = new CycleORMException('Erro', 0, null, ['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $ex->getContext());
        $ex->addContext('baz', 123);
        $this->assertEquals(['foo' => 'bar', 'baz' => 123], $ex->getContext());
    }
}
