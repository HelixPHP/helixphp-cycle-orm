<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Repository;

use CAFernandes\ExpressPHP\CycleORM\RepositoryFactory;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\ORM;
use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Tests\Repository\FakeFactory;
use CAFernandes\ExpressPHP\CycleORM\Tests\Repository\FakeSchema;

class RepositoryFactoryTest extends TestCase
{
    private function getRealOrm(): ORM
    {
        // Instancia ORM com fakes
        return new ORM(new FakeFactory(), new FakeSchema());
    }

    public function testGetRepositoryCachesInstance(): void
    {
        $orm = $this->getRealOrm();
        $factory = new RepositoryFactory($orm);
        $repo = $factory->getRepository(\stdClass::class);
        $repo2 = $factory->getRepository(\stdClass::class);
        $this->assertSame($repo, $repo2);
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

    public function testClearCacheAndStats(): void
    {
        $orm = $this->getRealOrm();
        $factory = new RepositoryFactory($orm);
        $factory->getRepository(\stdClass::class);
        $factory->clearCache();
        $stats = $factory->getStats();
        $this->assertEquals(0, $stats['cached_repositories']);
    }
}
