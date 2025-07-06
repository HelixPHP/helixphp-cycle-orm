<?php

namespace Helix\CycleORM\Tests\Http;

use Helix\CycleORM\Http\CycleRequest;
use Helix\Http\Request;
use PHPUnit\Framework\TestCase;

class CycleRequestExtraTest extends TestCase
{
    public function testSetAndGetUserAndAuth(): void
    {
        $mock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cycleReq = new CycleRequest($mock);
        $cycleReq->user = (object)['id' => 123];
        $cycleReq->auth = ['token' => 'xyz'];
        $this->assertEquals(123, $cycleReq->user->id);
        $this->assertEquals('xyz', $cycleReq->auth['token']);
    }
}
