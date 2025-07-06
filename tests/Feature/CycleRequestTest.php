<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Feature;

use CAFernandes\ExpressPHP\CycleORM\Tests\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Tests\Entities\User;
use CAFernandes\ExpressPHP\CycleORM\Tests\Entities\Post;
use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;
use Express\Http\Request;

class CycleRequestTest extends TestCase
{
    private CycleRequest $cycleRequest;
    private Request $originalRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalRequest = $this->createMock(Request::class);
        $this->cycleRequest = new CycleRequest($this->originalRequest);

        // Inject ORM services
        $this->cycleRequest->orm = $this->orm;
        $this->cycleRequest->em = $this->em;
        $this->cycleRequest->db = $this->dbal->database();
    }

    public function testMethodForwarding(): void
    {
        // Setup mock to expect method call
        $this->originalRequest
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        // Call should be forwarded to original request
        $result = $this->cycleRequest->getMethod();
        $this->assertEquals('POST', $result);
    }

    public function testPropertyForwarding(): void
    {
        // Create a real Request instance for property forwarding test
        $realRequest = new Request('GET', '/', '/');
        $cycleRequest = new CycleRequest($realRequest);

        // Set property on original request
        $realRequest->setAttribute('testProperty', 'test-value');

        // Should be accessible through CycleRequest
        $this->assertEquals('test-value', $cycleRequest->getAttribute('testProperty'));

        // Setting should also forward
        $cycleRequest->setAttribute('anotherProperty', 'another-value');
        $this->assertEquals('another-value', $realRequest->getAttribute('anotherProperty'));
    }

    public function testRepositoryAccess(): void
    {
        $userRepo = $this->cycleRequest->repository(User::class);
        $postRepo = $this->cycleRequest->repository(Post::class);

        $this->assertNotNull($userRepo);
        $this->assertNotNull($postRepo);
        $this->assertNotSame($userRepo, $postRepo);
    }

    public function testRepositoryWithEntityInstance(): void
    {
        $user = new User('Test User', 'test@example.com');
        $repo = $this->cycleRequest->repository($user);

        $this->assertNotNull($repo);

        // Should be same as getting by class
        $repoByClass = $this->cycleRequest->repository(User::class);
        $this->assertSame($repo, $repoByClass);
    }

    public function testRepositoryWithEmptyStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity class name cannot be empty');

        $this->cycleRequest->repository('');
    }

    public function testEntityCreation(): void
    {
        $userData = [
            'name' => 'Created User',
            'email' => 'created@example.com'
        ];

        $user = $this->cycleRequest->entity(User::class, $userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Created User', $user->name);
        $this->assertEquals('created@example.com', $user->email);
    }

    public function testEntityCreationWithEmptyStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity class name cannot be empty');

        $this->cycleRequest->entity('', []);
    }

    public function testFindEntity(): void
    {
        // Create and save a user first
        $user = new User('Find Test User', 'find@example.com');
        $this->em->persist($user);
        $this->em->run();

        // Find using CycleRequest
        $foundUser = $this->cycleRequest->find(User::class, $user->id);

        $this->assertNotNull($foundUser);
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('Find Test User', $foundUser->name);
        $this->assertEquals($user->id, $foundUser->id);
    }

    public function testFindNonExistentEntity(): void
    {
        $result = $this->cycleRequest->find(User::class, 99999);
        $this->assertNull($result);
    }

    public function testPaginationWithRealData(): void
    {
        // Create test data
        $user = new User('Pagination User', 'pagination@example.com');
        $this->em->persist($user);
        $this->em->run();

        // Create multiple posts
        for ($i = 1; $i <= 25; $i++) {
            $post = new Post("Post $i", "Content for post $i", $user->id);
            $this->em->persist($post);
        }
        $this->em->run();

        // Get repository and create select
        $postRepo = $this->cycleRequest->repository(Post::class);
        $query = $postRepo->select();

        // Test first page
        $page1 = $this->cycleRequest->paginate($query, 1, 10);

        $this->assertIsArray($page1);
        $this->assertArrayHasKey('data', $page1);
        $this->assertArrayHasKey('pagination', $page1);

        $pagination = $page1['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(25, $pagination['total']);
        $this->assertEquals(3, $pagination['last_page']);
        $this->assertEquals(1, $pagination['from']);
        $this->assertEquals(10, $pagination['to']);
        $this->assertTrue($pagination['has_more']);

        // Test second page
        $page2 = $this->cycleRequest->paginate($postRepo->select(), 2, 10);
        $pagination2 = $page2['pagination'];
        $this->assertEquals(2, $pagination2['current_page']);
        $this->assertEquals(11, $pagination2['from']);
        $this->assertEquals(20, $pagination2['to']);
        $this->assertTrue($pagination2['has_more']);

        // Test last page
        $page3 = $this->cycleRequest->paginate($postRepo->select(), 3, 10);
        $pagination3 = $page3['pagination'];
        $this->assertEquals(3, $pagination3['current_page']);
        $this->assertEquals(21, $pagination3['from']);
        $this->assertEquals(25, $pagination3['to']);
        $this->assertFalse($pagination3['has_more']);
    }

    public function testPaginationWithEmptyResults(): void
    {
        $postRepo = $this->cycleRequest->repository(Post::class);
        $query = $postRepo->select();

        $result = $this->cycleRequest->paginate($query, 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);

        $pagination = $result['pagination'];
        $this->assertEquals(0, $pagination['total']);
        $this->assertEquals(1, $pagination['last_page']);
        $this->assertEquals(0, $pagination['from']);
        $this->assertEquals(0, $pagination['to']);
        $this->assertFalse($pagination['has_more']);
    }

    public function testEntityValidation(): void
    {
        $rules = [
            'name' => 'required|string',
            'email' => 'required|email'
        ];

        $result = $this->cycleRequest->validateEntity($rules);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);

        // Basic implementation just returns valid for now
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testGetOriginalRequest(): void
    {
        $original = $this->cycleRequest->getOriginalRequest();
        $this->assertSame($this->originalRequest, $original);
    }

    public function testUserAndAuthProperties(): void
    {
        // Test user property
        $this->assertNull($this->cycleRequest->user);

        $userObject = (object)['id' => 1, 'name' => 'Test User'];
        $this->cycleRequest->user = $userObject;
        $this->assertSame($userObject, $this->cycleRequest->user);

        // Test auth property
        $this->assertEmpty($this->cycleRequest->auth);

        $authData = ['token' => 'abc123', 'role' => 'admin'];
        $this->cycleRequest->auth = $authData;
        $this->assertEquals($authData, $this->cycleRequest->auth);
    }

    public function testFullWorkflow(): void
    {
        // Create a user using entity helper
        $userData = ['name' => 'Workflow User', 'email' => 'workflow@example.com'];
        $user = $this->cycleRequest->entity(User::class, $userData);

        // Persist through entity manager
        $this->cycleRequest->em->persist($user);
        $this->cycleRequest->em->run();

        // Find the user using find helper
        $foundUser = $this->cycleRequest->find(User::class, $user->id);
        $this->assertNotNull($foundUser);
        $this->assertEquals('Workflow User', $foundUser->name);

        // Create a post for this user
        $postData = ['title' => 'User Post', 'content' => 'Post content', 'userId' => $user->id];
        $post = $this->cycleRequest->entity(Post::class, $postData);

        $this->cycleRequest->em->persist($post);
        $this->cycleRequest->em->run();

        // Use repository to find posts by user
        $postRepo = $this->cycleRequest->repository(Post::class);
        $userPosts = $postRepo->findAll(['userId' => $user->id]);

        $this->assertCount(1, $userPosts);
        $this->assertEquals('User Post', $userPosts[0]->title);
    }
}
