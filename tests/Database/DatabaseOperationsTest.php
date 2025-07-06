<?php

namespace Helix\CycleORM\Tests\Database;

use Helix\CycleORM\Tests\TestCase;
use Helix\CycleORM\Tests\Entities\User;
use Helix\CycleORM\Tests\Entities\Post;

class DatabaseOperationsTest extends TestCase
{
    public function testBasicEntityPersistence(): void
    {
        // Create a new user
        $user = new User('Alice Smith', 'alice@example.com');

        // Persist the user
        $this->em->persist($user);
        $this->em->run();

        // Verify user was saved with ID
        $this->assertNotNull($user->id);
        $this->assertIsInt($user->id);
        $this->assertGreaterThan(0, $user->id);
    }

    public function testEntityRetrieval(): void
    {
        // Create and save a user
        $user = new User('Bob Johnson', 'bob@example.com');
        $this->em->persist($user);
        $this->em->run();

        $userId = $user->id;

        // Clear entity manager to ensure fresh fetch
        $this->em = $this->app->getContainer()->get('cycle.em');

        // Retrieve the user
        $repository = $this->orm->getRepository(User::class);
        $foundUser = $repository->findByPK($userId);

        $this->assertNotNull($foundUser);
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('Bob Johnson', $foundUser->name);
        $this->assertEquals('bob@example.com', $foundUser->email);
        $this->assertEquals($userId, $foundUser->id);
    }

    public function testEntityUpdate(): void
    {
        // Create and save a user
        $user = new User('Charlie Brown', 'charlie@example.com');
        $this->em->persist($user);
        $this->em->run();

        $userId = $user->id;

        // Update the user
        $user->name = 'Charlie Brown Jr.';
        $user->email = 'charlie.jr@example.com';

        $this->em->persist($user);
        $this->em->run();

        // Fetch fresh instance to verify update
        $this->em = $this->app->getContainer()->get('cycle.em');
        $repository = $this->orm->getRepository(User::class);
        $updatedUser = $repository->findByPK($userId);

        $this->assertEquals('Charlie Brown Jr.', $updatedUser->name);
        $this->assertEquals('charlie.jr@example.com', $updatedUser->email);
    }

    public function testEntityDeletion(): void
    {
        // Create and save a user
        $user = new User('Diana Prince', 'diana@example.com');
        $this->em->persist($user);
        $this->em->run();

        $userId = $user->id;

        // Delete the user
        $this->em->delete($user);
        $this->em->run();

        // Verify user is deleted
        $repository = $this->orm->getRepository(User::class);
        $deletedUser = $repository->findByPK($userId);

        $this->assertNull($deletedUser);
    }

    public function testRelationshipHandling(): void
    {
        // Create and save a user
        $user = new User('Edward Norton', 'edward@example.com');
        $this->em->persist($user);
        $this->em->run();

        // Create posts for the user
        $post1 = new Post('First Post', 'Content of first post', $user->id);
        $post2 = new Post('Second Post', 'Content of second post', $user->id);

        $this->em->persist($post1);
        $this->em->persist($post2);
        $this->em->run();

        // Verify posts were saved
        $this->assertNotNull($post1->id);
        $this->assertNotNull($post2->id);

        // Test finding posts by user
        $postRepository = $this->orm->getRepository(Post::class);
        $userPosts = $postRepository->findAll(['userId' => $user->id]);

        $this->assertCount(2, $userPosts);
    }

    /**
     * @group integration
     */
    public function testComplexQueries(): void
    {
        // Create test data
        $user1 = new User('Frank Miller', 'frank@example.com');
        $user2 = new User('Grace Lee', 'grace@example.com');

        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->run();

        // Create posts
        $post1 = new Post('PHP Tutorial', 'Learning PHP basics', $user1->id);
        $post2 = new Post('JavaScript Guide', 'Modern JavaScript features', $user1->id);
        $post3 = new Post('Database Design', 'SQL best practices', $user2->id);

        $this->em->persist($post1);
        $this->em->persist($post2);
        $this->em->persist($post3);
        $this->em->run();

        // Test complex queries using repository
        $postRepository = $this->orm->getRepository(Post::class);

        // Find posts containing 'PHP'
        $phpPosts = $postRepository->findAll(['title' => ['like', '%PHP%']]);
        $this->assertCount(1, $phpPosts);
        $this->assertEquals('PHP Tutorial', $phpPosts[0]->title);

        // Find posts by specific user
        $frankPosts = $postRepository->findAll(['userId' => $user1->id]);
        $this->assertCount(2, $frankPosts);
    }

    /**
     * @group integration
     */
    public function testTransactionHandling(): void
    {
        $database = $this->dbal->database();
        $transaction = $database->begin();

        try {
            // Create users in transaction
            $user1 = new User('Hannah Davis', 'hannah@example.com');
            $user2 = new User('Ian Foster', 'ian@example.com');

            $this->em->persist($user1);
            $this->em->persist($user2);
            $this->em->run();

            // Verify users were created
            $this->assertNotNull($user1->id);
            $this->assertNotNull($user2->id);

            // Commit transaction
            $transaction->commit();

            // Verify data persists after commit
            $userRepository = $this->orm->getRepository(User::class);
            $foundUser1 = $userRepository->findByPK($user1->id);
            $foundUser2 = $userRepository->findByPK($user2->id);

            $this->assertNotNull($foundUser1);
            $this->assertNotNull($foundUser2);
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    /**
     * @group integration
     */
    public function testTransactionRollback(): void
    {
        $database = $this->dbal->database();
        $transaction = $database->begin();

        try {
            // Create user in transaction
            $user = new User('Jack Wilson', 'jack@example.com');
            $this->em->persist($user);
            $this->em->run();

            $userId = $user->id;
            $this->assertNotNull($userId);

            // Rollback transaction
            $transaction->rollback();

            // Verify user was not persisted after rollback
            $this->em = $this->app->getContainer()->get('cycle.em');
            $userRepository = $this->orm->getRepository(User::class);
            $foundUser = $userRepository->findByPK($userId);

            $this->assertNull($foundUser);
        } catch (\Exception $e) {
            $transaction->rollback();
            throw $e;
        }
    }

    public function testBatchOperations(): void
    {
        $users = [];

        // Create multiple users
        for ($i = 1; $i <= 10; $i++) {
            $user = new User("User $i", "user$i@example.com");
            $users[] = $user;
            $this->em->persist($user);
        }

        // Save all users in batch
        $this->em->run();

        // Verify all users were saved
        foreach ($users as $user) {
            $this->assertNotNull($user->id);
        }

        // Verify count in database
        $userRepository = $this->orm->getRepository(User::class);
        $allUsers = $userRepository->findAll();
        $this->assertGreaterThanOrEqual(10, count($allUsers));
    }

    public function testDatabaseConstraints(): void
    {
        // Test that we can create users with different emails
        $user1 = new User('Kevin Clark', 'kevin@example.com');
        $user2 = new User('Laura White', 'laura@example.com');

        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->run();

        $this->assertNotNull($user1->id);
        $this->assertNotNull($user2->id);
        $this->assertNotEquals($user1->id, $user2->id);
    }
}
