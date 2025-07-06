<?php

namespace Helix\CycleORM\Tests\Integration;

use Helix\CycleORM\Tests\TestCase;
use Helix\CycleORM\Tests\Entities\User;
use Helix\CycleORM\Tests\Entities\Post;
use Helix\CycleORM\Http\CycleRequest;
use Helix\Http\Request;
use Helix\Http\Response;

/**
 * @group integration
 */
class HelixIntegrationTest extends TestCase
{
    public function testFullApplicationLifecycle(): void
    {
        // Test that the provider boots successfully
        $this->provider->boot();

        // Test that we can access ORM through the container
        $orm = $this->app->getContainer()->get('cycle.orm');
        $this->assertNotNull($orm);

        // Test that we can access entity manager
        $em = $this->app->getContainer()->get('cycle.em');
        $this->assertNotNull($em);
    }

    public function testMiddlewareIntegration(): void
    {
        // Boot the provider to register middleware
        $this->provider->boot();

        // Create a mock request
        $request = $this->createMock(Request::class);
        $cycleRequest = new CycleRequest($request);

        // Inject ORM services into the request
        $cycleRequest->orm = $this->orm;
        $cycleRequest->em = $this->em;
        $cycleRequest->db = $this->dbal->database();

        // Test that we can access repositories through the request
        $userRepo = $cycleRequest->repository(User::class);
        $this->assertNotNull($userRepo);

        $postRepo = $cycleRequest->repository(Post::class);
        $this->assertNotNull($postRepo);
    }

    public function testEntityOperationsThroughRequest(): void
    {
        $this->provider->boot();

        $request = $this->createMock(Request::class);
        $cycleRequest = new CycleRequest($request);

        $cycleRequest->orm = $this->orm;
        $cycleRequest->em = $this->em;
        $cycleRequest->db = $this->dbal->database();

        // Create a user using the entity helper
        $userData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $user = $cycleRequest->entity(User::class, $userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);

        // Persist the user
        $cycleRequest->em->persist($user);
        $cycleRequest->em->run();

        // Verify user was saved
        $this->assertNotNull($user->id);

        // Find the user using the request helper
        $foundUser = $cycleRequest->find(User::class, $user->id);
        $this->assertNotNull($foundUser);
        $this->assertEquals('John Doe', $foundUser->name);
    }

    public function testRequestPaginationFeature(): void
    {
        $this->provider->boot();

        // Create test data
        $userId = $this->createUser('Test User', 'test@example.com');
        for ($i = 1; $i <= 25; $i++) {
            $this->createPost("Post $i", "Content $i", $userId);
        }

        $request = $this->createMock(Request::class);
        $cycleRequest = new CycleRequest($request);

        $cycleRequest->orm = $this->orm;
        $cycleRequest->em = $this->em;
        $cycleRequest->db = $this->dbal->database();

        // Get posts repository and create a select query
        $postRepo = $cycleRequest->repository(Post::class);
        $query = $postRepo->select();

        // Test pagination
        $result = $cycleRequest->paginate($query, 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);

        $pagination = $result['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(25, $pagination['total']);
        $this->assertEquals(3, $pagination['last_page']);
        $this->assertTrue($pagination['has_more']);
    }

    public function testTransactionMiddleware(): void
    {
        $this->provider->boot();

        $request = $this->createMock(Request::class);
        $cycleRequest = new CycleRequest($request);

        $cycleRequest->orm = $this->orm;
        $cycleRequest->em = $this->em;
        $cycleRequest->db = $this->dbal->database();

        // Start a transaction manually to test transaction handling
        $database = $cycleRequest->db;
        $transaction = $database->begin();

        try {
            // Create user in transaction
            $userData = ['name' => 'Transaction User', 'email' => 'trans@example.com'];
            $user = $cycleRequest->entity(User::class, $userData);
            $cycleRequest->em->persist($user);
            $cycleRequest->em->run();

            $this->assertNotNull($user->id);

            // Commit transaction
            $transaction->commit();

            // Verify user exists after commit
            $foundUser = $cycleRequest->find(User::class, $user->id);
            $this->assertNotNull($foundUser);
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    public function testRepositoryFactoryIntegration(): void
    {
        $this->provider->boot();

        $factory = $this->app->getContainer()->get('cycle.repository');

        // Test getting repositories through factory
        $userRepo = $factory->getRepository(User::class);
        $postRepo = $factory->getRepository(Post::class);

        $this->assertNotNull($userRepo);
        $this->assertNotNull($postRepo);

        // Test repository caching
        $userRepo2 = $factory->getRepository(User::class);
        $this->assertSame($userRepo, $userRepo2);

        // Test factory stats
        $stats = $factory->getStats();
        $this->assertArrayHasKey('cached_repositories', $stats);
        $this->assertArrayHasKey('custom_repositories', $stats);
        $this->assertArrayHasKey('entities', $stats);

        $this->assertEquals(2, $stats['cached_repositories']);
    }

    public function testHealthCheckIntegration(): void
    {
        $this->provider->boot();

        // Test that database connection is healthy
        $database = $this->dbal->database();
        $result = $database->query('SELECT 1 as health')->fetch();
        $this->assertEquals(1, $result['health']);

        // Test ORM is functional
        $userRepo = $this->orm->getRepository(User::class);
        $this->assertNotNull($userRepo);
    }

    public function testCommandRegistration(): void
    {
        // Test in CLI mode
        if (php_sapi_name() === 'cli') {
            $this->provider->boot();

            $commands = $this->app->getContainer()->get('cycle.commands');

            $this->assertIsArray($commands);
            $this->assertArrayHasKey('cycle:schema', $commands);
            $this->assertArrayHasKey('cycle:migrate', $commands);
            $this->assertArrayHasKey('make:entity', $commands);
        } else {
            $this->markTestSkipped('Command registration test requires CLI environment');
        }
    }
}
