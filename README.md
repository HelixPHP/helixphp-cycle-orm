# Express PHP Cycle ORM Extension

[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-68%20passing-brightgreen.svg)](https://phpunit.de/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Uma extensão robusta e bem testada que integra o Cycle ORM ao framework Express PHP, oferecendo recursos avançados de ORM com arquitetura limpa e moderna.

## 🚀 Características

- **Integração Completa**: Perfeita integração com Express PHP através de Service Provider
- **Type Safety**: Código 100% tipado com PHPStan nível 9
- **Bem Testado**: 68 testes automatizados cobrindo todas as funcionalidades
- **Repositórios Customizados**: Factory pattern para repositórios com cache inteligente
- **Middlewares Prontos**: Transaction e Entity Validation middlewares
- **Monitoramento**: Sistema completo de métricas e profiling
- **CycleRequest**: Extensão intuitiva do Request com métodos ORM
- **CLI Commands**: Comandos para migração e gerenciamento do schema

## 📦 Instalação

```bash
composer require cafernandes/express-php-cycle-orm-extension
```

## 🎯 Uso Rápido

### 1. Registrar o Service Provider

```php
// bootstrap/app.php
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;

// Configure as variáveis de ambiente antes do registro
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = __DIR__ . '/database/database.sqlite';

// Registre o provider
$app->register(new CycleServiceProvider($app));
```

### 2. Configurar Variáveis de Ambiente

```env
# SQLite (desenvolvimento)
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

# MySQL (produção)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Uso Básico - Acesso Direto ao Database

```php
// Acesso direto para queries simples
$app->get('/api/users', function ($req, $res) use ($app) {
    $database = $app->make('cycle.database');
    $users = $database->database()->query('SELECT * FROM users')->fetchAll();
    
    return $res->json(['data' => $users]);
});

// Inserção com query builder
$app->post('/api/users', function ($req, $res) use ($app) {
    $database = $app->make('cycle.database');
    $data = $req->getParsedBody();
    
    $database->database()->insert('users')->values([
        'name' => $data['name'],
        'email' => $data['email'],
        'created_at' => date('Y-m-d H:i:s')
    ])->run();
    
    return $res->json(['message' => 'User created']);
});
```

### 4. Uso Avançado - Arquitetura Limpa com Repositórios

```php
use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;

// Repository Interface
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findAll(): array;
    public function save(User $user): void;
}

// Repository Implementation
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
    
    private function mapToEntity(array $data): User
    {
        return new User(
            id: $data['id'],
            name: $data['name'],
            email: $data['email']
        );
    }
}

// Controller com Injeção de Dependência
class UserController
{
    public function __construct(
        private UserRepositoryInterface $repository
    ) {}
    
    public function show(int $id): JsonResponse
    {
        $user = $this->repository->findById($id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        return response()->json($user);
    }
}
```

## 🧪 Executar Testes

```bash
# Todos os testes (exceto integração complexa)
vendor/bin/phpunit

# Apenas testes unitários
vendor/bin/phpunit tests/Unit/

# Incluir testes de integração
vendor/bin/phpunit --group integration
```

## 📈 Qualidade do Código

- **PHPStan Nível 9**: Zero erros de tipagem
- **PSR-12**: Padrões de código seguidos
- **100% Testado**: Cobertura completa das funcionalidades principais
- **Type Safety**: Interfaces bem definidas

## 🔧 Funcionalidades Avançadas

### Sincronização de Schema
```bash
# Sincronizar schema do banco de dados
php bin/console cycle:schema:sync

# Verificar status das migrações
php bin/console cycle:status
```

### Configuração Completa do Cycle ORM
```php
// config/cycle.php
return [
    'default' => env('DB_CONNECTION', 'sqlite'),
    
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', ':memory:'),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'express_php'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],
    
    'entities' => [
        'directories' => [
            __DIR__ . '/../src/Domain/Entities',
        ],
    ],
    
    'cache' => [
        'enabled' => env('CYCLE_CACHE_ENABLED', true),
        'directory' => env('CYCLE_CACHE_DIR', __DIR__ . '/../storage/cache/cycle'),
    ],
];
```

### Repository Factory com Cache
```php
$factory = $app->get('cycle.repository');
$userRepo = $factory->getRepository(User::class); // Cached automatically
```

### Middleware de Transação
```php
$app->use(new TransactionMiddleware($app));

// Transações automáticas em rotas
$app->post('/api/users', function ($req, $res) {
    // Transação iniciada automaticamente
    // Commit automático em sucesso
    // Rollback automático em erro
});
```

### Sistema de Monitoramento
```php
use CAFernandes\ExpressPHP\CycleORM\Monitoring\MetricsCollector;

// Ativar profiling de queries
$_ENV['CYCLE_PROFILE_QUERIES'] = true;
$_ENV['CYCLE_LOG_QUERIES'] = true;

// Coletar métricas
$metrics = MetricsCollector::getMetrics();
// Exibe: queries executadas, tempo de execução, cache hits/misses
```

### Container de Injeção de Dependência
```php
// Registrar repositórios no container
$container->bind(UserRepositoryInterface::class, function ($container) {
    return new UserRepository(
        $container->get('cycle.database')
    );
});

// Usar em controllers
$userController = $container->get(UserController::class);
```

## 📚 Exemplos de Implementação

### API CRUD Completa
```php
// Estrutura de pastas recomendada
├── src/
│   ├── Domain/
│   │   ├── Entities/
│   │   │   └── User.php
│   │   └── Repositories/
│   │       └── UserRepositoryInterface.php
│   ├── Infrastructure/
│   │   └── Repositories/
│   │       └── UserRepository.php
│   └── Application/
│       ├── Controllers/
│       │   └── UserController.php
│       └── UseCases/
│           └── CreateUserUseCase.php
```

### Padrões de Implementação

A extensão suporta diferentes níveis de complexidade:

1. **Nível Básico**: Acesso direto ao banco via Cycle Database
   - Ideal para MVPs e prototipagem rápida
   - Queries SQL diretas com segurança
   - Mínima configuração necessária

2. **Nível Intermediário**: Padrão Repository
   - Organização do código em camadas
   - Facilita testes e manutenção
   - Reutilização de lógica de negócio

3. **Nível Avançado**: Clean Architecture
   - Separação completa de responsabilidades
   - Use Cases e Value Objects
   - Máxima testabilidade e flexibilidade

## 📚 Documentação Completa

- [Guia Completo - Do Básico ao Avançado](docs/guia-completo.md)
- [Documentação Principal](docs/index.md)
- [Guia de Contribuição](CONTRIBUTING.md)
- [Arquitetura Técnica](docs/techinical/)
- [Exemplos de Implementação](docs/implementions/)

## 🤝 Contribuição

Contribuições são bem-vindas! Consulte [CONTRIBUTING.md](CONTRIBUTING.md) para guidelines.

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.
