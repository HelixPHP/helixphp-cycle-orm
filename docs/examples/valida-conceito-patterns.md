# Padrões de Uso - Projeto valida_conceito

Este documento descreve os padrões de implementação encontrados no projeto `valida_conceito`, que demonstra o uso prático da extensão HelixPHP Cycle ORM.

## Visão Geral

O projeto `valida_conceito` apresenta duas abordagens distintas para implementar uma API CRUD:

1. **Abordagem Básica** (`index.php`): Acesso direto ao banco de dados
2. **Abordagem Clean Architecture** (`index_clean.php`): Separação completa de camadas

## Configuração Inicial

### 1. Configuração do Ambiente

```php
// Definir variáveis de ambiente
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = __DIR__ . '/database/database.sqlite';
$_ENV['APP_DEBUG'] = 'true';

// Registrar o Service Provider
$app->register(new CycleServiceProvider($app));
```

### 2. Estrutura de Configuração

O projeto utiliza dois arquivos de configuração:

**config/database.php** - Configuração simples:
```php
return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/database.sqlite',
        ],
    ],
];
```

**config/cycle.php** - Configuração completa:
```php
return [
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [...],
        'mysql' => [...],
    ],
    'entities' => [
        'directories' => [
            __DIR__ . '/../src/Domain/Entities',
        ],
    ],
    'cache' => [
        'enabled' => true,
        'directory' => __DIR__ . '/../storage/cache/cycle',
    ],
];
```

## Padrão Básico - Acesso Direto

### Características
- Usa diretamente o serviço `cycle.database`
- Queries SQL diretas
- Mapeamento manual de resultados
- Ideal para projetos simples ou prototipagem

### Exemplo de Implementação

```php
// Listar todos os usuários
$app->get('/api/users', function ($req, $res) use ($app) {
    try {
        $database = $app->make('cycle.database');
        $users = $database->database()->query('SELECT * FROM users')->fetchAll();
        
        return $res->json([
            'status' => 'success',
            'data' => $users
        ]);
    } catch (\Exception $e) {
        return $res->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Criar usuário
$app->post('/api/users', function ($req, $res) use ($app) {
    try {
        $database = $app->make('cycle.database');
        $data = $req->getParsedBody();
        
        // Validação básica
        if (empty($data['name']) || empty($data['email'])) {
            return $res->json([
                'status' => 'error',
                'message' => 'Name and email are required'
            ], 400);
        }
        
        // Inserir usando query builder
        $database->database()->insert('users')->values([
            'name' => $data['name'],
            'email' => $data['email'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ])->run();
        
        $lastInsertId = $database->database()->lastInsertID();
        
        return $res->json([
            'status' => 'success',
            'data' => ['id' => $lastInsertId]
        ], 201);
    } catch (\Exception $e) {
        return $res->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
```

## Padrão Clean Architecture

### Características
- Separação completa de camadas
- Domain-Driven Design (DDD)
- Inversão de dependência
- Testável e escalável

### Estrutura de Diretórios

```
src/
├── Domain/
│   ├── Entities/
│   │   └── User.php
│   ├── ValueObjects/
│   │   ├── Email.php
│   │   └── Name.php
│   └── Repositories/
│       └── UserRepositoryInterface.php
├── Infrastructure/
│   ├── Container/
│   │   └── DIContainer.php
│   └── Repositories/
│       └── UserRepository.php
└── Application/
    ├── Controllers/
    │   └── UserController.php
    ├── UseCases/
    │   ├── CreateUserUseCase.php
    │   └── ListUsersUseCase.php
    └── DTOs/
        ├── ApiResponse.php
        └── UserDTO.php
```

### Implementação das Camadas

#### 1. Entidade de Domínio

```php
namespace App\Domain\Entities;

class User
{
    private ?int $id;
    private Name $name;
    private Email $email;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Name $name,
        Email $email,
        ?int $id = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public static function create(string $name, string $email): self
    {
        return new self(
            new Name($name),
            new Email($email)
        );
    }
}
```

#### 2. Repositório

```php
namespace App\Infrastructure\Repositories;

use Cycle\Database\DatabaseInterface;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private DatabaseInterface $database
    ) {}

    public function findById(int $id): ?User
    {
        $result = $this->database->query(
            'SELECT * FROM users WHERE id = ?',
            [$id]
        )->fetch();

        return $result ? $this->mapToEntity($result) : null;
    }

    public function save(User $user): void
    {
        $data = $this->mapToArray($user);
        
        if ($user->getId()) {
            $this->database->update('users', $data, ['id' => $user->getId()])->run();
        } else {
            $this->database->insert('users')->values($data)->run();
        }
    }

    private function mapToEntity(array $data): User
    {
        return User::fromArray($data);
    }
}
```

#### 3. Use Case

```php
namespace App\Application\UseCases;

class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private ValidatorInterface $validator
    ) {}

    public function execute(CreateUserDTO $dto): Result
    {
        // Validar dados
        $errors = $this->validator->validate($dto);
        if (!empty($errors)) {
            return Result::failure($errors);
        }

        // Criar entidade
        $user = User::create($dto->name, $dto->email);

        // Persistir
        try {
            $this->userRepository->save($user);
            return Result::success($user);
        } catch (\Exception $e) {
            return Result::failure(['error' => 'Failed to save user']);
        }
    }
}
```

#### 4. Controller

```php
namespace App\Application\Controllers;

class UserController
{
    public function __construct(
        private CreateUserUseCase $createUserUseCase,
        private ListUsersUseCase $listUsersUseCase
    ) {}

    public function store(Request $request): ApiResponse
    {
        $dto = CreateUserDTO::fromArray($request->getParsedBody());
        $result = $this->createUserUseCase->execute($dto);

        if ($result->isSuccess()) {
            return ApiResponse::success($result->getData(), 201);
        }

        return ApiResponse::error($result->getErrors(), 400);
    }
}
```

### Container de Injeção de Dependência

```php
// Configuração do container
$container = new DIContainer();

// Registrar serviços do Cycle
$container->bind('cycle.database', function () use ($app) {
    return $app->make('cycle.database');
});

// Registrar repositórios
$container->bind(UserRepositoryInterface::class, function ($container) {
    return new UserRepository($container->get('cycle.database'));
});

// Registrar use cases
$container->bind(CreateUserUseCase::class, function ($container) {
    return new CreateUserUseCase(
        $container->get(UserRepositoryInterface::class),
        $container->get(ValidatorInterface::class)
    );
});

// Registrar controllers
$container->bind(UserController::class, function ($container) {
    return new UserController(
        $container->get(CreateUserUseCase::class),
        $container->get(ListUsersUseCase::class)
    );
});
```

## Sincronização de Schema

O projeto utiliza comandos do Cycle para gerenciar o banco de dados:

```bash
# Sincronizar schema
php bin/console cycle:schema:sync

# Executar migrações
php bin/console cycle:migrate

# Verificar status
php bin/console cycle:status
```

## Tratamento de Erros

Ambos os padrões implementam tratamento consistente de erros:

```php
// Middleware de erro global
$app->use(function ($req, $res, $next) {
    try {
        return $next($req, $res);
    } catch (\Exception $e) {
        return $res->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ], 500);
    }
});
```

## Validação de Dados

### Padrão Básico
```php
// Validação inline
if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    return $res->json(['error' => 'Invalid email'], 400);
}
```

### Padrão Clean Architecture
```php
// Validação em Value Objects
class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }
}
```

## Testes

O projeto suporta testes em ambos os padrões:

```php
// Teste do padrão básico
public function testCreateUser()
{
    $response = $this->client->post('/api/users', [
        'json' => [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]
    ]);
    
    $this->assertEquals(201, $response->getStatusCode());
}

// Teste do padrão Clean Architecture
public function testCreateUserUseCase()
{
    $repository = $this->createMock(UserRepositoryInterface::class);
    $useCase = new CreateUserUseCase($repository);
    
    $result = $useCase->execute(new CreateUserDTO('Test', 'test@example.com'));
    
    $this->assertTrue($result->isSuccess());
}
```

## Considerações de Performance

1. **Cache de Repositórios**: Reutilizar instâncias via container
2. **Queries Otimizadas**: Usar índices apropriados
3. **Lazy Loading**: Carregar relações apenas quando necessário
4. **Connection Pooling**: Configurar pool de conexões para produção

## Conclusão

O projeto `valida_conceito` demonstra que a extensão HelixPHP Cycle ORM é flexível o suficiente para suportar tanto implementações simples quanto arquiteturas complexas. A escolha entre os padrões depende dos requisitos do projeto:

- Use o **Padrão Básico** para: MVPs, prototipagem, APIs simples
- Use o **Padrão Clean Architecture** para: Aplicações empresariais, sistemas complexos, quando testabilidade é crucial