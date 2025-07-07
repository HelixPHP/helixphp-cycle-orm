<?php

namespace PivotPHP\CycleORM\Tests\Exceptions;

use PivotPHP\CycleORM\Exceptions\CycleORMException;
use PHPUnit\Framework\TestCase;

class CycleORMExceptionTest extends TestCase
{
    public function testContextManipulation(): void
    {
        $ex = new CycleORMException('Erro', 0, null, ['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $ex->getContext());
        $ex->addContext('baz', 123);
        $this->assertEquals(['foo' => 'bar', 'baz' => 123], $ex->getContext());
        $ex->setContext(['a' => 1]);
        $this->assertEquals(['a' => 1], $ex->getContext());
    }
}
