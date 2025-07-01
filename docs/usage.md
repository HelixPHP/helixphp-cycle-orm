# Usage Guide - Express-PHP Cycle ORM Extension

> **Dica:** Consulte também o [Guia Técnico e Quick Start](./guia-tecnico-quickstart.md) para um resumo completo das funcionalidades, exemplos e melhores práticas.

## 🚀 Quick Start

### 1. Instalação

```bash
composer require express-php/cycle-orm-extension
```

### 2. Configuração do Ambiente

Crie ou edite o arquivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=express_api
DB_USERNAME=root
DB_PASSWORD=

CYCLE_SCHEMA_CACHE=true
CYCLE_AUTO_SYNC=false
CYCLE_SCHEMA_STRICT=false
CYCLE_LOG_QUERIES=false
```

### 3. Gerar Primeira Entidade

```bash
php express make:entity User
```

Isso cria `app/Models/User.php`:

```php
<?php
namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'users')]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $email;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
```

### 4. Sincronizar o Schema

```bash
php express cycle:schema --sync
```

### 5. Implementação Básica de API

```php
<?php
require_once 'vendor/autoload.php';

use Express\Core\Application;
use App\Models\User;

$app = new Application();

// Listar usuários
$app->get('/api/users', function($req, $res) {
    $users = $req->repository(User::class)->findAll();
    $res->json(['users' => $users]);
});

// Criar usuário
$app->post('/api/users', function($req, $res) {
    $user = $req->entity(User::class, $req->body);
    $req->em->persist($user);
    $res->status(201)->json(['user' => $user]);
});

$app->run();
```

## 🛠️ Core Features

### Automatic Service Injection

The extension automatically injects these services into your request object:

- `$req->orm` - Cycle ORM instance
- `$req->em` - Entity Manager for persistence
- `$req->db` - Database Manager
- `$req->repository(EntityClass)` - Get repository for entity
- `$req->entity(EntityClass, data)` - Create entity with data
- `$req->find(EntityClass, id)` - Find entity by primary key
- `$req->paginate(query, page, perPage)` - Paginate query results

### Transaction Management

Transactions are handled automatically:

```php
$app->post('/api/users', function($req, $res) {
    // Transaction starts automatically
    $user = $req->entity(User::class, $req->body);
    $req->em->persist($user);
    // Auto-commit on success, auto-rollback on exception
});
```

### Entity Validation

Built-in validation helpers:

```php
$app->post('/api/users', function($req, $res) {
    $user = $req->entity(User::class, $req->body);

    // Validate entity
    $validation = $req->validateEntity($user);
    if (!$validation['valid']) {
        return $res->status(400)->json(['errors' => $validation['errors']]);
    }

    $req->em->persist($user);
    $res->status(201)->json(['user' => $user]);
});
```

## 🔍 Querying Data

### Basic Queries

```php
// Find all
$users = $req->repository(User::class)->findAll();

// Find by criteria
$activeUsers = $req->repository(User::class)
    ->select()
    ->where('active', true)
    ->fetchAll();

// Find with ordering
$users = $req->repository(User::class)
    ->select()
    ->orderBy('createdAt', 'DESC')
    ->limit(10)
    ->fetchAll();
```

### Advanced Filtering and Pagination

```php
use CAFernandes\ExpressPHP\CycleORM\Helpers\CycleHelpers;

$app->get('/api/users/search', function($req, $res) {
    $query = $req->repository(User::class)->select();

    // Apply filters
    $filters = $req->query['filters'] ?? [];
    $query = CycleHelpers::applyFilters($query, $filters, ['name', 'email']);

    // Apply search
    $search = $req->query['search'] ?? null;
    $query = CycleHelpers::applySearch($query, $search, ['name', 'email']);

    // Apply sorting
    $sortBy = $req->query['sort_by'] ?? 'createdAt';
    $direction = $req->query['direction'] ?? 'desc';
    $query = CycleHelpers::applySorting($query, $sortBy, $direction);

    // Paginate
    $page = (int)($req->query['page'] ?? 1);
    $result = $req->paginate($query, $page, 15);

    $res->json($result);
});
```

## 🔗 Relationships

### Defining Relationships

```php
// User.php
#[Entity(table: 'users')]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[HasMany(target: 'App\Models\Post', load: 'lazy')]
    public array $posts = [];
}

// Post.php
#[Entity(table: 'posts')]
class Post
{
    #[Column(type: 'primary')]
    public int $id;

    #[BelongsTo(target: 'App\Models\User', load: 'eager')]
    public User $author;
}
```

### Loading Relationships

```php
// Eager loading
$user = $req->repository(User::class)
    ->select()
    ->load('posts')
    ->where('id', $userId)
    ->fetchOne();

// Nested loading
$user = $req->repository(User::class)
    ->select()
    ->load('posts.comments')
    ->where('id', $userId)
    ->fetchOne();

// Conditional loading
$user = $req->repository(User::class)
    ->select()
    ->load('posts', [
        'method' => \Cycle\ORM\Select::SINGLE_QUERY,
        'load' => function($q) {
            $q->where('published', true)->orderBy('createdAt', 'DESC');
        }
    ])
    ->fetchOne();
```

## 🔧 CLI Commands

### Available Commands

```bash
# Generate entity
php express make:entity EntityName

# Show schema information
php express cycle:schema

# Sync schema to database
php express cycle:schema --sync

# Run migrations
php express cycle:migrate

# Rollback last migration
php express cycle:migrate --rollback

# Show migration status
php express cycle:migrate --status

# Show overall status
php express cycle:status

# Clear schema cache
php express cycle:schema --clear-cache
```

### Command Examples

```bash
# Create User entity with migration
php express make:entity User --migration

# Sync schema and run migrations
php express cycle:schema --sync
php express cycle:migrate

# Check system status
php express cycle:status
```

## 🎯 Best Practices

### 1. Entity Design

```php
#[Entity(table: 'users')]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'string', length: 255, unique: true)]
    public string $email;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
```

### 2. Repository Pattern

```php
// Custom repository
class UserRepository extends Repository
{
    public function findActiveUsers(): array
    {
        return $this->select()
            ->where('active', true)
            ->orderBy('createdAt', 'DESC')
            ->fetchAll();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->select()
            ->where('email', $email)
            ->fetchOne();
    }
}

// Usage in routes
$app->get('/api/users/active', function($req, $res) {
    $repository = $req->repository(User::class);
    $users = $repository->findActiveUsers();
    $res->json(['users' => $users]);
});
```

### 3. Error Handling

```php
$app->post('/api/users', function($req, $res) {
    try {
        $user = $req->entity(User::class, $req->body);

        // Validate
        $validation = $req->validateEntity($user);
        if (!$validation['valid']) {
            return $res->status(400)->json([
                'success' => false,
                'errors' => $validation['errors']
            ]);
        }

        $req->em->persist($user);

        $res->status(201)->json([
            'success' => true,
            'data' => $user
        ]);

    } catch (\Exception $e) {
        $res->status(500)->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});
```

### 4. Performance Optimization

```php
// Use pagination for large datasets
$result = $req->paginate($query, $page, $perPage);

// Use eager loading to avoid N+1 queries
$users = $req->repository(User::class)
    ->select()
    ->load('posts', 'profile')
    ->fetchAll();

// Use select specific columns when needed
$users = $req->repository(User::class)
    ->select()
    ->columns(['id', 'name', 'email'])
    ->fetchAll();
```
