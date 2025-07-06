# HelixPHP Cycle ORM

<div align="center">

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Stable Version](https://img.shields.io/badge/version-1.0.0-brightgreen.svg)](https://github.com/HelixPHP/helixphp-cycle-orm/releases)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%209-success.svg)](https://phpstan.org/)
[![Tests](https://img.shields.io/badge/tests-67%20passed-success.svg)](https://github.com/HelixPHP/helixphp-cycle-orm/actions)

Robust and well-tested Cycle ORM integration for HelixPHP microframework

</div>

## 🚀 Features

- **Seamless Integration**: Deep integration with HelixPHP Core
- **Type Safety**: Full type safety with PHPStan Level 9
- **Repository Pattern**: Built-in repository pattern support
- **Performance Monitoring**: Query logging and performance profiling
- **Middleware Support**: Transaction and validation middleware
- **Health Checks**: Database health monitoring
- **Zero Configuration**: Works out of the box with sensible defaults

## 📦 Installation

```bash
composer require helixphp/cycle-orm
```

## 🔧 Quick Start

### 1. Register the Service Provider

```php
use Helix\Core\Application;
use Helix\CycleORM\CycleServiceProvider;

$app = new Application();
$app->register(new CycleServiceProvider());
```

### 2. Configure Database

```php
// config/cycle.php
return [
    'database' => [
        'default' => 'default',
        'databases' => [
            'default' => ['connection' => 'sqlite']
        ],
        'connections' => [
            'sqlite' => [
                'driver' => \Cycle\Database\Driver\SQLite\SQLiteDriver::class,
                'options' => [
                    'connection' => 'sqlite:database.db',
                ]
            ]
        ]
    ]
];
```

### 3. Define Entities

```php
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(repository: UserRepository::class)]
class User
{
    #[Column(type: 'primary')]
    private int $id;
    
    #[Column(type: 'string')]
    private string $name;
    
    #[Column(type: 'string', unique: true)]
    private string $email;
    
    // Getters and setters...
}
```

### 4. Use in Routes

```php
$app->get('/users', function (CycleRequest $request) {
    $users = $request->getRepository(User::class)->findAll();
    
    return $request->response()->json($users);
});

$app->post('/users', function (CycleRequest $request) {
    $user = new User();
    $user->setName($request->input('name'));
    $user->setEmail($request->input('email'));
    
    $request->persist($user);
    
    return $request->response()->json($user, 201);
});
```

## 🎯 Core Features

### Repository Pattern

```php
// Custom repository
class UserRepository extends Repository
{
    public function findByEmail(string $email): ?User
    {
        return $this->findOne(['email' => $email]);
    }
    
    public function findActive(): array
    {
        return $this->select()
            ->where('active', true)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }
}
```

### Transaction Middleware

```php
use Helix\CycleORM\Middleware\TransactionMiddleware;

// Automatic transaction handling
$app->post('/api/orders', 
    new TransactionMiddleware(),
    function (CycleRequest $request) {
        // All database operations are wrapped in a transaction
        $order = new Order();
        $request->persist($order);
        
        // If an exception occurs, transaction is rolled back
        foreach ($request->input('items') as $item) {
            $orderItem = new OrderItem();
            $request->persist($orderItem);
        }
        
        return $request->response()->json($order);
    }
);
```

### Query Monitoring

```php
use Helix\CycleORM\Monitoring\QueryLogger;

// Enable query logging
$logger = $app->get(QueryLogger::class);
$logger->enable();

// Get query statistics
$stats = $logger->getStatistics();
// [
//     'total_queries' => 42,
//     'total_time' => 0.123,
//     'queries' => [...]
// ]
```

### Health Checks

```php
use Helix\CycleORM\Health\CycleHealthCheck;

$app->get('/health', function () use ($app) {
    $health = $app->get(CycleHealthCheck::class);
    $status = $health->check();
    
    return [
        'status' => $status->isHealthy() ? 'healthy' : 'unhealthy',
        'database' => $status->getData()
    ];
});
```

## 🛠️ Advanced Usage

### Entity Validation Middleware

```php
use Helix\CycleORM\Middleware\EntityValidationMiddleware;

$app->post('/users',
    new EntityValidationMiddleware(User::class, [
        'name' => 'required|string|min:3',
        'email' => 'required|email|unique:users,email'
    ]),
    $handler
);
```

### Performance Profiling

```php
use Helix\CycleORM\Monitoring\PerformanceProfiler;

$profiler = $app->get(PerformanceProfiler::class);
$profiler->startProfiling();

// Your database operations...

$profile = $profiler->stopProfiling();
// [
//     'duration' => 0.456,
//     'memory_peak' => 2097152,
//     'queries_count' => 15
// ]
```

### Custom Commands

```php
// Create entity command
php vendor/bin/helix cycle:entity User

// Run migrations
php vendor/bin/helix cycle:migrate

// Update schema
php vendor/bin/helix cycle:schema

// Check database status
php vendor/bin/helix cycle:status
```

## 🧪 Testing

```bash
# Run all tests
composer test

# Run specific test suite
composer test:unit
composer test:feature
composer test:integration

# Run with coverage
composer test-coverage
```

## 📚 Documentation

- [Integration Guide](docs/integration-guide.md)
- [Complete Guide](docs/guia-completo.md)
- [API Reference](docs/quick-reference.md)
- [Examples](examples/)

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 📄 License

HelixPHP Cycle ORM is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Credits

- Created by [Caio Alberto Fernandes](https://github.com/CAFernandes)
- Built on top of [Cycle ORM](https://cycle-orm.dev/)
- Part of the [HelixPHP](https://github.com/HelixPHP) ecosystem

---

<div align="center">
  <sub>Built with HelixPHP - The modern PHP microframework</sub>
</div>