# Express PHP Cycle ORM Extension

[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-68%20passing-brightgreen.svg)](https://phpunit.de/)
[![PSR-12](https://img.shields.io/badge/PSR-12-blue.svg)](https://www.php-fig.org/psr/psr-12/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Uma extensão robusta e bem testada que integra o Cycle ORM ao framework Express PHP, oferecendo recursos avançados de ORM com arquitetura limpa e moderna. Fornece integração transparente através do CycleRequest, permitindo acesso direto aos serviços ORM em suas rotas.

## 🚀 Características

- **Integração Completa**: Perfeita integração com Express PHP através de Service Provider
- **CycleRequest**: Wrapper inteligente que adiciona métodos ORM ao Request padrão
- **Type Safety**: Código 100% tipado com PHPStan nível 9 e PSR-12
- **Bem Testado**: 68 testes automatizados cobrindo todas as funcionalidades
- **Repositórios Customizados**: Factory pattern para repositórios com cache inteligente
- **Middlewares Prontos**: CycleMiddleware, Transaction e Entity Validation
- **Monitoramento**: Sistema completo de métricas, profiling e logging de queries
- **Compatibilidade**: PHP 8.1+ (recomendado PHP 8.3 para evitar avisos)
- **CLI Commands**: Comandos para migração e gerenciamento do schema

## 📦 Instalação

```bash
composer require cafernandes/express-php-cycle-orm-extension
```

## 🎯 Uso Rápido

### 1. Configuração Inicial

```php
// public/index.php
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use CAFernandes\ExpressPHP\CycleORM\Middleware\CycleMiddleware;

// IMPORTANTE: Define o diretório de trabalho
chdir(dirname(__DIR__));

// Configure as variáveis de ambiente
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = __DIR__ . '/../database/database.sqlite';

// Registre o provider
$app->register(new CycleServiceProvider($app));

// Adicione o CycleMiddleware para usar CycleRequest
$app->use(new CycleMiddleware($app));
```

### ⚠️ Importante
- Sempre defina o diretório de trabalho com `chdir()`
- Crie o diretório `app/Entities` mesmo se não usar entidades anotadas
- Use PHP 8.1 ou 8.3 para evitar avisos de depreciação do Spiral Core

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

### 3. Uso com CycleRequest (Recomendado)

Com o CycleMiddleware ativo, todas as rotas recebem um CycleRequest com métodos ORM:

```php
// Listar usuários usando CycleRequest
$app->get('/api/users', function ($req, $res) {
    // $req é agora um CycleRequest
    $db = $req->getContainer()->get('cycle.database');
    $users = $db->database()->query('SELECT * FROM users')->fetchAll();
    
    return $res->json([
        'data' => $users,
        'request_type' => get_class($req) // CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest
    ]);
});

// Métodos disponíveis no CycleRequest
$app->get('/api/example', function ($req, $res) {
    // Repositório para entidade
    $userRepo = $req->repository(User::class);
    
    // Criar nova entidade
    $user = $req->entity(User::class, ['name' => 'John']);
    
    // Paginação
    $paginated = $req->paginate(User::class, 20, 1);
    
    // Propriedades ORM disponíveis
    $orm = $req->orm;  // Instância do ORM
    $em = $req->em;    // Entity Manager
    
    return $res->json(['message' => 'CycleRequest features']);
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
# Todos os testes
composer test

# Com relatório de cobertura
composer test-coverage

# Verificar qualidade do código
composer phpstan       # PHPStan nível 9
composer cs:check      # PSR-12 compliance
composer cs:fix        # Corrigir PSR-12
```

## 📈 Qualidade do Código

- **PHPStan Nível 9**: Zero erros de tipagem em análise estática
- **PSR-12**: Conformidade total com padrões de código
- **68 Testes**: Cobertura completa com PHPUnit
- **Type Safety**: Interfaces e tipos bem definidos
- **CI/CD**: GitHub Actions para testes automatizados

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

- [Guia de Integração Completo](docs/integration-guide.md) 🆕
- [Guia Completo - Do Básico ao Avançado](docs/guia-completo.md)
- [Documentação Principal](docs/index.md)
- [Resolução de Problemas](docs/integration-guide.md#resolução-de-problemas)
- [Exemplos Práticos](docs/integration-guide.md#exemplos-práticos)
- [Guia de Contribuição](CONTRIBUTING.md)

## 🤝 Contribuição

Contribuições são bem-vindas! Consulte [CONTRIBUTING.md](CONTRIBUTING.md) para guidelines.

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.
