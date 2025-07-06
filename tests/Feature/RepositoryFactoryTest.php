<?php

namespace Helix\CycleORM\Tests\Feature;

use Helix\CycleORM\Tests\TestCase;
use Helix\CycleORM\Tests\Entities\User;
use Helix\CycleORM\Tests\Entities\Post;
use Helix\CycleORM\RepositoryFactory;
use Cycle\ORM\RepositoryInterface;

class RepositoryFactoryTest extends TestCase
{
    private RepositoryFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new RepositoryFactory($this->orm);
        $this->app->getContainer()->bind('cycle.repository', fn() => $this->factory);
    }

    public function testGetRepositoryReturnsCorrectInstance(): void
    {
        $userRepo = $this->factory->getRepository(User::class);
        $postRepo = $this->factory->getRepository(Post::class);

        $this->assertInstanceOf(RepositoryInterface::class, $userRepo);
        $this->assertInstanceOf(RepositoryInterface::class, $postRepo);
        $this->assertNotSame($userRepo, $postRepo);
    }

    public function testRepositoryCaching(): void
    {
        // First call
        $repo1 = $this->factory->getRepository(User::class);

        // Second call should return same instance
        $repo2 = $this->factory->getRepository(User::class);

        $this->assertSame($repo1, $repo2);
    }

    public function testGetRepositoryWithEntityInstance(): void
    {
        $user = new User('Test User', 'test@example.com');
        $repo = $this->factory->getRepository($user);

        $this->assertInstanceOf(RepositoryInterface::class, $repo);

        // Should be same as getting by class name
        $repoByClass = $this->factory->getRepository(User::class);
        $this->assertSame($repo, $repoByClass);
    }

    public function testClearCache(): void
    {
        // Get repository and verify it's cached
        $repo1 = $this->factory->getRepository(User::class);
        $stats = $this->factory->getStats();
        $this->assertEquals(1, $stats['cached_repositories']);

        // Clear cache
        $this->factory->clearCache();
        $stats = $this->factory->getStats();
        $this->assertEquals(0, $stats['cached_repositories']);

        // Getting repository again should call ORM again
        $repo2 = $this->factory->getRepository(User::class);
        // Note: Cycle ORM may return the same repository instance
        // The important thing is that our cache was cleared
        $this->assertInstanceOf(RepositoryInterface::class, $repo2);
    }

    public function testGetStats(): void
    {
        // Get some repositories
        $this->factory->getRepository(User::class);
        $this->factory->getRepository(Post::class);

        $stats = $this->factory->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cached_repositories', $stats);
        $this->assertArrayHasKey('custom_repositories', $stats);
        $this->assertArrayHasKey('entities', $stats);

        $this->assertEquals(2, $stats['cached_repositories']);
        $this->assertEquals(0, $stats['custom_repositories']);
        $this->assertCount(2, $stats['entities']);
        $this->assertContains(User::class, $stats['entities']);
        $this->assertContains(Post::class, $stats['entities']);
    }

    public function testRegisterCustomRepositoryWithValidClass(): void
    {
        // For this test, we'll use a mock repository class
        $customRepoClass = new class implements RepositoryInterface {
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

        $customRepoClassName = get_class($customRepoClass);

        // This should not throw an exception
        $this->factory->registerCustomRepository(User::class, $customRepoClassName);

        $stats = $this->factory->getStats();
        $this->assertEquals(1, $stats['custom_repositories']);
    }

    public function testRegisterCustomRepositoryWithInvalidClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Repository class NonExistentClass does not exist');

        $this->factory->registerCustomRepository(User::class, 'NonExistentClass');
    }

    public function testRegisterCustomRepositoryWithNonRepositoryClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement RepositoryInterface');

        $this->factory->registerCustomRepository(User::class, \stdClass::class);
    }

    public function testRepositoryFunctionalityWithRealData(): void
    {
        // Create test data
        $user = new User('Factory Test User', 'factory@example.com');
        $this->em->persist($user);
        $this->em->run();

        // Get repository through factory
        $userRepo = $this->factory->getRepository(User::class);

        // Test repository functionality
        $foundUser = $userRepo->findByPK($user->id);
        $this->assertNotNull($foundUser);
        $this->assertEquals('Factory Test User', $foundUser->name);

        // Test findAll
        $allUsers = $userRepo->findAll();
        $this->assertGreaterThanOrEqual(1, count($allUsers));

        // Test findOne
        $oneUser = $userRepo->findOne(['email' => 'factory@example.com']);
        $this->assertNotNull($oneUser);
        $this->assertEquals('Factory Test User', $oneUser->name);
    }

    public function testMultipleFactoryInstances(): void
    {
        // Create second factory instance
        $factory2 = new RepositoryFactory($this->orm);

        // They should return repository instances
        $repo1 = $this->factory->getRepository(User::class);
        $repo2 = $factory2->getRepository(User::class);

        // Each factory maintains its own cache, but Cycle ORM may return same instances
        $this->assertInstanceOf(RepositoryInterface::class, $repo1);
        $this->assertInstanceOf(RepositoryInterface::class, $repo2);

        // But within same factory, should be cached
        $repo1Again = $this->factory->getRepository(User::class);
        $this->assertSame($repo1, $repo1Again);
    }
}
