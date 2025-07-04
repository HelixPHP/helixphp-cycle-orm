<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Http;

use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;
use Express\Http\Request;
use PHPUnit\Framework\TestCase;

class CycleRequestTest extends TestCase
{
    public function testDynamicMethodAndPropertyForwarding(): void
    {
        $mock = $this->createMock(Request::class);
        $mock->expects($this->once())->method('getMethod')->willReturn('POST');
        $cycleReq = new CycleRequest($mock);
        $this->assertEquals('POST', $cycleReq->getMethod());
        $mock->foo = 'bar';
        $this->assertEquals('bar', $cycleReq->foo);
        $cycleReq->foo = 'baz';
        $this->assertEquals('baz', $mock->foo);
    }

    public function testExtraProperties(): void
    {
        $mock = $this->createMock(Request::class);
        $cycleReq = new CycleRequest($mock);
        $cycleReq->user = (object)['id' => 1];
        $cycleReq->auth = ['token' => 'abc'];
        $this->assertEquals(1, $cycleReq->user->id);
        $this->assertEquals('abc', $cycleReq->auth['token']);
    }
}
