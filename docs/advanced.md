# Advanced Usage - Express-PHP Cycle ORM Extension

## 🏗️ Custom Repositories

### Creating Custom Repositories

```php
namespace App\Repositories;

use Cycle\ORM\Select\Repository;
use App\Models\User;

class UserRepository extends Repository
{
    public function findActiveUsers(): array
    {
        return $this->select()
            ->where('active', true)
            ->orderBy('createdAt', 'DESC')
            ->fetchAll();
    }

    public function findByDateRange(\DateTime $start, \DateTime $end): array
    {
        return $this->select()
            ->where('createdAt', '>=', $start)
            ->where('createdAt', '<=', $end)
            ->fetchAll();
    }

    public function getPopularUsers(int $minPosts = 5): array
    {
        return $this->select()
            ->load('posts')
            ->where('posts.id', 'IS NOT', null)
            ->groupBy('id')
            ->having('COUNT(posts.id)', '>=', $minPosts)
            ->fetchAll();
    }
}
```

### Registering Custom Repositories

In your entity:

```php
#[Entity(
    table: 'users',
    repository: 'App\Repositories\UserRepository'
)]
class User
{
    // Entity definition
}
```

## 🔄 Advanced Queries

### Complex Joins and Subqueries

```php
$app->get('/api/analytics/users', function($req, $res) {
    $orm = $req->orm;

    // Complex query with subqueries
    $users = $orm->getRepository(User::class)
        ->select()
        ->where('id', 'IN', function($select) {
            return $select
                ->from('posts')
                ->columns('author_id')
                ->where('created_at', '>=', new \DateTime('-30 days'))
                ->groupBy('author_id')
                ->having('COUNT(*)', '>', 3);
        })
        ->load('posts', [
            'method' => \Cycle\ORM\Select::SINGLE_QUERY,
            'load' => function($q) {
                $q->where('created_at', '>=', new \DateTime('-30 days'));
            }
        ])
        ->fetchAll();

    $res->json(['users' => $users]);
});
```

### Raw Database Queries

```php
$app->get('/api/stats/complex', function($req, $res) {
    $db = $req->db->database();

    // Complex aggregation query
    $stats = $db->query(
        'SELECT
            u.id,
            u.name,
            COUNT(p.id) as posts_count,
            AVG(p.views) as avg_views,
            SUM(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_posts
         FROM users u
         LEFT JOIN posts p ON u.id = p.author_id
         WHERE u.active = 1
         GROUP BY u.id, u.name
         HAVING posts_count > ?
         ORDER BY avg_views DESC
         LIMIT 10',
        [5]
    )->fetchAll();

    $res->json(['stats' => $stats]);
});
```

## 🎭 Custom Middleware

### Performance Monitoring Middleware

```php
namespace App\Middleware;

use Express\Http\Request;
use Express\Http\Response;
use Express\Core\Application;

class CyclePerformanceMiddleware
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        $startTime = microtime(true);
        $startQueries = $this->getQueryCount();

        $next();

        $endTime = microtime(true);
        $endQueries = $this->getQueryCount();

        $duration = ($endTime - $startTime) * 1000;
        $queryCount = $endQueries - $startQueries;

        // Add performance headers
        $res->header('X-Response-Time', round($duration, 2) . 'ms');
        $res->header('X-Query-Count', $queryCount);

        // Log slow requests
        if ($duration > 1000) { // > 1 second
            error_log("Slow request: {$req->getUri()} - {$duration}ms - {$queryCount} queries");
        }
    }

    private function getQueryCount(): int
    {
        // Implementation depends on your logging setup
        return 0;
    }
}
```

### Caching Middleware

```php
namespace App\Middleware;

class CycleCacheMiddleware
{
    private Application $app;
    private $cache;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->cache = $app->has('cache') ? $app->make('cache') : null;
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        if (!$this->cache || $req->getMethod() !== 'GET') {
            $next();
            return;
        }

        $cacheKey = $this->getCacheKey($req);
        $cached = $this->cache->get($cacheKey);

        if ($cached) {
            $res->header('X-Cache', 'HIT');
            $res->json($cached);
            return;
        }

        // Capture response
        ob_start();
        $next();
        $output = ob_get_clean();

        // Cache successful responses
        if ($res->getStatusCode() === 200) {
            $data = json_decode($output, true);
            $this->cache->put($cacheKey, $data, 300); // 5 minutes
            $res->header('X-Cache', 'MISS');
        }

        echo $output;
    }

    private function getCacheKey(Request $req): string
    {
        return 'api:' . md5($req->getUri() . serialize($req->query));
    }
}
```

## 🔄 Event System

### Custom Event Listeners

```php
// Register in service provider
$this->app->addAction('cycle.entity.persisted', function($context) {
    $entity = $context['entity'];

    if ($entity instanceof User) {
        // Send welcome email for new users
        if ($context['action'] === 'created') {
            $this->sendWelcomeEmail($entity);
        }
    }
});

$this->app->addAction('cycle.query.executed', function($context) {
    $query = $context['query'];
    $time = $context['time'];

    // Log slow queries
    if ($time > 1000) {
        error_log("Slow query: {$query} - {$time}ms");
    }
});
```

### Entity Lifecycle Events

```php
// In your entity
class User
{
    public function __construct()
    {
        $this->createdAt = new \DateTime();

        // Dispatch event
        if (function_exists('app')) {
            app()->fireAction('user.creating', ['user' => $this]);
        }
    }

    public function setEmail(string $email): void
    {
        $oldEmail = $this->email;
        $this->email = $email;

        if (function_exists('app') && $oldEmail !== $email) {
            app()->fireAction('user.email.changed', [
                'user' => $this,
                'old_email' => $oldEmail,
                'new_email' => $email
            ]);
        }
    }
}
```

## 🧪 Testing with Cycle ORM

### Test Base Class

```php
namespace Tests;

use PHPUnit\Framework\TestCase;
use Express\Core\Application;
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;

abstract class CycleTestCase extends TestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application();

        // Configure test database
        $this->app->config([
            'cycle.database' => [
                'default' => 'sqlite',
                'databases' => ['default' => ['connection' => 'sqlite']],
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => ':memory:'
                    ]
                ]
            ]
        ]);

        // Register Cycle ORM
        $provider = new CycleServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        // Sync schema
        $this->syncSchema();
    }

    protected function syncSchema(): void
    {
        $migrator = $this->app->make('cycle.migrator');
        $migrator->run();
    }

    protected function createUser(array $data = []): User
    {
        $user = new User();
        $user->name = $data['name'] ?? 'Test User';
        $user->email = $data['email'] ?? 'test@example.com';

        $em = $this->app->make('cycle.em');
        $em->persist($user);
        $em->run();

        return $user;
    }
}
```

### Entity Factory

```php
namespace Tests\Factories;

class UserFactory
{
    public static function create(array $attributes = []): User
    {
        $user = new User();
        $user->name = $attributes['name'] ?? 'Test User ' . rand(1000, 9999);
        $user->email = $attributes['email'] ?? 'test' . rand(1000, 9999) . '@example.com';
        $user->active = $attributes['active'] ?? true;

        return $user;
    }

    public static function createMany(int $count, array $attributes = []): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = self::create($attributes);
        }
        return $users;
    }
}
```

### Test Example

```php
namespace Tests\Feature;

use Tests\CycleTestCase;
use Tests\Factories\UserFactory;
use App\Models\User;

class UserTest extends CycleTestCase
{
    public function testCreateUser(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $user = UserFactory::create($userData);
        $em = $this->app->make('cycle.em');
        $em->persist($user);
        $em->run();

        $this->assertNotNull($user->getId());
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john@example.com', $user->getEmail());
    }

    public function testUserRepository(): void
    {
        // Create test data
        $activeUser = UserFactory::create(['active' => true]);
        $inactiveUser = UserFactory::create(['active' => false]);

        $em = $this->app->make('cycle.em');
        $em->persist($activeUser);
        $em->persist($inactiveUser);
        $em->run();

        // Test repository method
        $repository = $this->app->make('cycle.orm')->getRepository(User::class);
        $activeUsers = $repository->findActiveUsers();

        $this->assertCount(1, $activeUsers);
        $this->assertTrue($activeUsers[0]->isActive());
    }
}
```

## 🚀 Performance Optimization

### Query Optimization

```php
// ❌ Bad: N+1 queries
foreach ($users as $user) {
    $posts = $user->posts; // Triggers separate query for each user
}

// ✅ Good: Eager loading
$users = $req->repository(User::class)
    ->select()
    ->load('posts')
    ->fetchAll();

// ✅ Better: Selective loading
$users = $req->repository(User::class)
    ->select()
    ->load('posts', [
        'method' => \Cycle\ORM\Select::SINGLE_QUERY,
        'load' => function($q) {
            $q->where('published', true)
              ->orderBy('created_at', 'DESC')
              ->limit(5);
        }
    ])
    ->fetchAll();
```

### Batch Operations

```php
$app->post('/api/users/bulk', function($req, $res) {
    $userData = $req->body['users'] ?? [];
    $em = $req->em;

    $users = [];
    foreach ($userData as $data) {
        $user = $req->entity(User::class, $data);
        $em->persist($user);
        $users[] = $user;
    }

    // Single transaction for all users
    $em->run();

    $res->json([
        'success' => true,
        'created' => count($users),
        'users' => $users
    ]);
});
```

### Schema Caching

```php
// In production, enable schema caching
// .env
CYCLE_SCHEMA_CACHE=true

// Warm up cache in deployment
php express cycle:schema --sync
```
