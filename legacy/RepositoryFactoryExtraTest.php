<?php

namespace PivotPHP\CycleORM\Tests\Repository;

use PivotPHP\CycleORM\RepositoryFactory;
use Cycle\ORM\ORM;
use PHPUnit\Framework\TestCase;
use PivotPHP\CycleORM\Tests\Repository\FakeFactory;
use PivotPHP\CycleORM\Tests\Repository\FakeSchema;

class RepositoryFactoryExtraTest extends TestCase
{
    private function getRealOrm(): ORM
    {
        return new ORM(new FakeFactory(), new FakeSchema());
    }

    public function testRegisterCustomRepositoryWithInvalidInterface(): void
    {
        $orm = $this->getRealOrm();
        $factory = new RepositoryFactory($orm);
        eval('class FakeRepo {}');
        $this->expectException(\InvalidArgumentException::class);
        $factory->registerCustomRepository('User', 'FakeRepo');
    }

    public function testRegisterCustomRepository(): void
    {
        $orm = $this->getRealOrm();
        $factory = new RepositoryFactory($orm);
        $factory->registerCustomRepository(
            \stdClass::class,
            \stdClass::class
        );
        $this->assertTrue(true);
    }
}
