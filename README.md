# PivotPHP Cycle ORM

<div align="center">

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Stable Version](https://img.shields.io/badge/version-1.0.0-brightgreen.svg)](https://github.com/PivotPHP/pivotphp-cycle-orm/releases)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%209-success.svg)](https://phpstan.org/)
[![Tests](https://img.shields.io/badge/tests-67%20passed-success.svg)](https://github.com/PivotPHP/pivotphp-cycle-orm/actions)

Robust and well-tested Cycle ORM integration for PivotPHP microframework

</div>

## 🚀 Features

- **Seamless Integration**: Deep integration with PivotPHP Core
- **Type Safety**: Full type safety with PHPStan Level 9
- **Repository Pattern**: Built-in repository pattern support
- **Performance Monitoring**: Query logging and performance profiling
- **Middleware Support**: Transaction and validation middleware
- **Health Checks**: Database health monitoring
- **Zero Configuration**: Works out of the box with sensible defaults

## 📦 Installation

```bash
composer require pivotphp/cycle-orm
```

### Development Setup

When developing locally with both pivotphp-core and pivotphp-cycle-orm:

1. Clone both repositories in the same parent directory:
```bash
git clone https://github.com/CAFernandes/pivotphp-core.git
git clone https://github.com/CAFernandes/pivotphp-cycle-orm.git
```

2. Install dependencies:
```bash
cd pivotphp-cycle-orm
composer install
```

The `composer.json` is configured to use the local path `../pivotphp-core` for development.

**Note**: The CI/CD pipeline automatically adjusts the composer configuration to use the GitHub repository instead of the local path.

## 🔧 Quick Start

### 1. Register the Service Provider

```php
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\CycleORM\CycleServiceProvider;

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
use PivotPHP\Core\CycleORM\Middleware\TransactionMiddleware;

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
use PivotPHP\Core\CycleORM\Monitoring\QueryLogger;

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
use PivotPHP\Core\CycleORM\Health\CycleHealthCheck;

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
use PivotPHP\Core\CycleORM\Middleware\EntityValidationMiddleware;

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
use PivotPHP\Core\CycleORM\Monitoring\PerformanceProfiler;

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
php vendor/bin/pivotphp cycle:entity User

// Run migrations
php vendor/bin/pivotphp cycle:migrate

// Update schema
php vendor/bin/pivotphp cycle:schema

// Check database status
php vendor/bin/pivotphp cycle:status
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

PivotPHP Cycle ORM is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Credits

- Created by [Caio Alberto Fernandes](https://github.com/CAFernandes)
- Built on top of [Cycle ORM](https://cycle-orm.dev/)
- Part of the [PivotPHP](https://github.com/PivotPHP) ecosystem

---

<div align="center">
  <sub>Built with PivotPHP - The modern PHP microframework</sub>
</div>
