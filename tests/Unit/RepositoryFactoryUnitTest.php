<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Unit;

use CAFernandes\ExpressPHP\CycleORM\RepositoryFactory;
use CAFernandes\ExpressPHP\CycleORM\Tests\Entities\User;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;
use PHPUnit\Framework\TestCase;

class RepositoryFactoryUnitTest extends TestCase
{
    private RepositoryFactory $factory;
    private ORMInterface $mockOrm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOrm = $this->createMock(ORMInterface::class);
        $this->factory = new RepositoryFactory($this->mockOrm);
    }

    public function testGetRepositoryReturnsRepositoryInterface(): void
    {
        $mockRepo = $this->createMock(RepositoryInterface::class);

        $this->mockOrm
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);

        $result = $this->factory->getRepository(User::class);

        $this->assertSame($mockRepo, $result);
    }

    public function testRepositoryCaching(): void
    {
        $mockRepo = $this->createMock(RepositoryInterface::class);

        $this->mockOrm
            ->expects($this->once()) // Should only be called once due to caching
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);

        // First call
        $result1 = $this->factory->getRepository(User::class);

        // Second call should return cached version
        $result2 = $this->factory->getRepository(User::class);

        $this->assertSame($result1, $result2);
    }

    public function testClearCache(): void
    {
        $mockRepo1 = $this->createMock(RepositoryInterface::class);
        $mockRepo2 = $this->createMock(RepositoryInterface::class);

        $this->mockOrm
            ->expects($this->exactly(2)) // Should be called twice after cache clear
            ->method('getRepository')
            ->with(User::class)
            ->willReturnOnConsecutiveCalls($mockRepo1, $mockRepo2);

        // First call
        $result1 = $this->factory->getRepository(User::class);

        // Clear cache
        $this->factory->clearCache();

        // Second call should hit ORM again
        $result2 = $this->factory->getRepository(User::class);

        // Results should be different instances (not cached)
        $this->assertNotSame($result1, $result2);
    }

    public function testGetStatsInitialState(): void
    {
        $stats = $this->factory->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cached_repositories', $stats);
        $this->assertArrayHasKey('custom_repositories', $stats);
        $this->assertArrayHasKey('entities', $stats);

        $this->assertEquals(0, $stats['cached_repositories']);
        $this->assertEquals(0, $stats['custom_repositories']);
        $this->assertEmpty($stats['entities']);
    }

    public function testGetStatsAfterRepositoryAccess(): void
    {
        $mockRepo = $this->createMock(RepositoryInterface::class);

        $this->mockOrm
            ->method('getRepository')
            ->willReturn($mockRepo);

        // Access some repositories
        $this->factory->getRepository(User::class);
        $this->factory->getRepository(\stdClass::class);

        $stats = $this->factory->getStats();

        $this->assertEquals(2, $stats['cached_repositories']);
        $this->assertCount(2, $stats['entities']);
        $this->assertContains(User::class, $stats['entities']);
        $this->assertContains(\stdClass::class, $stats['entities']);
    }

    public function testRegisterCustomRepositoryWithValidClass(): void
    {
        // Use a real class that implements RepositoryInterface for this test
        $validRepoClass = new class implements RepositoryInterface {
            public function findByPK(mixed $id): ?object
            {
                return null;
            }
            public function findOne(array $scope = [], array $orderBy = []): ?object
            {
                return null;
            }
            public function findAll(array $scope = [], array $orderBy = []): iterable
            {
                return [];
            }
        };

        $className = get_class($validRepoClass);

        // This should not throw an exception
        $this->factory->registerCustomRepository(User::class, $className);

        $stats = $this->factory->getStats();
        $this->assertEquals(1, $stats['custom_repositories']);
    }

    public function testRegisterCustomRepositoryWithNonExistentClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Repository class NonExistentClass does not exist');

        $this->factory->registerCustomRepository(User::class, 'NonExistentClass');
    }

    public function testRegisterCustomRepositoryWithInvalidInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement RepositoryInterface');

        $this->factory->registerCustomRepository(User::class, \stdClass::class);
    }

    public function testGetRepositoryWithEntityInstance(): void
    {
        $user = new User('Test User', 'test@example.com');
        $mockRepo = $this->createMock(RepositoryInterface::class);

        $this->mockOrm
            ->expects($this->once())
            ->method('getRepository')
            ->with($user)
            ->willReturn($mockRepo);

        $result = $this->factory->getRepository($user);

        $this->assertSame($mockRepo, $result);
    }
}
