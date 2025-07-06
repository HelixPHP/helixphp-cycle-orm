# Express PHP Cycle ORM Extension

[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-68%20passing-brightgreen.svg)](https://phpunit.de/)
[![PSR-12](https://img.shields.io/badge/PSR-12-blue.svg)](https://www.php-fig.org/psr/psr-12/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Uma extensão robusta e bem testada que integra o Cycle ORM ao framework Express PHP, oferecendo recursos avançados de ORM com arquitetura limpa e moderna.

## 🚀 Características

- **Integração Completa**: Perfeita integração com Express PHP através de Service Provider
- **Attributes Pattern**: Acesso aos serviços ORM via attributes do Request
- **Type Safety**: Código 100% tipado com PHPStan nível 9 e PSR-12
- **Bem Testado**: 68 testes automatizados cobrindo todas as funcionalidades
- **Repositórios Customizados**: Factory pattern para repositórios com cache inteligente
- **Monitoramento**: Sistema completo de métricas, profiling e logging de queries
- **Compatibilidade**: PHP 8.1+ (recomendado PHP 8.3)
- **CLI Commands**: Comandos para migração e gerenciamento do schema

## 📦 Instalação

```bash
composer require cafernandes/express-php-cycle-orm-extension
```

## 🎯 Guia de Integração Completo

### 1. Configuração Inicial

```php
// public/index.php
use Express\Core\Application;
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// IMPORTANTE: Define o diretório de trabalho
chdir(dirname(__DIR__));

// Configure as variáveis de ambiente
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = __DIR__ . '/../database/database.sqlite';
$_ENV['CYCLE_ENTITY_DIRS'] = 'src/Entities'; // Diretórios das entidades

$app = new Application();

// Registre o provider
$app->register(new CycleServiceProvider($app));
```

### 2. Middleware de Integração (Solução Recomendada)

⚠️ **Nota Importante**: Devido a limitações de design no CycleMiddleware atual, recomendamos usar o seguinte middleware customizado:

```php
// Middleware Cycle ORM com Attributes Pattern
$app->use(function ($req, $res, $next) use ($app) {
    $container = $app->getContainer();
    
    if (!$container->has('cycle.orm')) {
        throw new \RuntimeException('Cycle ORM not properly registered');
    }

    // Obtém os serviços do Cycle ORM
    $orm = $container->get('cycle.orm');
    $em = $container->get('cycle.em');
    $db = $container->get('cycle.database');
    $repository = $container->get('cycle.repository');

    // Injeta serviços através de attributes do Express PHP
    $req->setAttribute('cycle.orm', $orm);
    $req->setAttribute('cycle.em', $em);
    $req->setAttribute('cycle.db', $db);
    $req->setAttribute('cycle.repository', $repository);

    // Helper methods como closures
    $req->setAttribute('repository', function(string $entityClass) use ($repository) {
        return $repository->getRepository($entityClass);
    });

    $req->setAttribute('entity', function(string $entityClass, array $data = []) use ($orm) {
        return $orm->make($entityClass, $data);
    });

    $req->setAttribute('entityManager', function() use ($em) {
        return $em;
    });

    // Continua com o Request original
    $next($req, $res);
});
```

### 3. Definindo Entidades

```php
// src/Entities/User.php
namespace App\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity(repository: \App\Repositories\UserRepository::class)]
#[Table(name: 'users')]
class User
{
    #[Column(type: 'primary')]
    private ?int $id = null;

    #[Column(type: 'string', nullable: false)]
    private string $name;

    #[Column(type: 'string', nullable: false, unique: true)]
    private string $email;

    #[Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters e setters...
}
```

### 4. Repositórios Customizados

```php
// src/Repositories/UserRepository.php
namespace App\Repositories;

use App\Entities\User;
use Cycle\ORM\Select\Repository;

class UserRepository extends Repository
{
    public function findByEmail(string $email): ?User
    {
        return $this->findOne(['email' => $email]);
    }

    public function findRecentUsers(int $days = 30): iterable
    {
        $date = new \DateTime();
        $date->sub(new \DateInterval('P' . $days . 'D'));
        
        return $this->select()
            ->where('created_at', '>=', $date)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }
}
```

### 5. Usando nos Controllers

```php
// src/Controllers/UserController.php
namespace App\Controllers;

use App\Entities\User;
use Express\Http\Request;
use Express\Http\Response;

class UserController
{
    public function index(Request $request): Response
    {
        // Obtém o helper de repositório
        $repositoryHelper = $request->getAttribute('repository');
        $repository = $repositoryHelper(User::class);
        
        $users = $repository->findAll();
        
        return (new Response())->json([
            'success' => true,
            'data' => array_map(fn(User $u) => $u->toArray(), $users)
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $request->getBody();
        
        // Obtém helpers
        $repositoryHelper = $request->getAttribute('repository');
        $entityManagerHelper = $request->getAttribute('entityManager');
        
        $repository = $repositoryHelper(User::class);
        $entityManager = $entityManagerHelper();
        
        // Cria nova entidade
        $user = new User();
        $user->setName($data['name']);
        $user->setEmail($data['email']);
        
        // Persiste
        $entityManager->persist($user);
        $entityManager->run();
        
        return (new Response())->status(201)->json([
            'success' => true,
            'data' => $user->toArray()
        ]);
    }
}
```

### 6. Rotas da Aplicação

```php
// public/index.php

// Instancia o controller
$userController = new UserController();

// Define as rotas
$app->get('/api/users', [$userController, 'index']);
$app->get('/api/users/{id}', [$userController, 'show']);
$app->post('/api/users', [$userController, 'store']);
$app->put('/api/users/{id}', [$userController, 'update']);
$app->delete('/api/users/{id}', [$userController, 'destroy']);

$app->run();
```

## 🔧 Configuração Avançada

### Variáveis de Ambiente

```env
# Banco de Dados
DB_CONNECTION=sqlite           # ou mysql
DB_DATABASE=database/app.db    # caminho do SQLite ou nome do banco MySQL
DB_HOST=127.0.0.1             # apenas para MySQL
DB_PORT=3306                  # apenas para MySQL
DB_USERNAME=root              # apenas para MySQL
DB_PASSWORD=secret            # apenas para MySQL

# Cycle ORM
CYCLE_ENTITY_DIRS=src/Entities,app/Entities  # diretórios das entidades
CYCLE_LOG_QUERIES=true        # log de queries em dev
CYCLE_PROFILE_QUERIES=true    # profiling em dev

# Aplicação
APP_ENV=development
APP_DEBUG=true
```

### Estrutura de Diretórios Recomendada

```
projeto/
├── app/
│   └── Entities/          # Alternativa para entidades
├── bin/
│   └── console           # CLI commands
├── config/
│   └── cycle.php         # Configurações do Cycle ORM
├── database/
│   ├── migrations/       # Arquivos de migração
│   └── database.sqlite   # Banco SQLite
├── public/
│   └── index.php        # Entry point
├── src/
│   ├── Controllers/     # Controllers da aplicação
│   ├── Entities/        # Entidades do domínio
│   └── Repositories/    # Repositórios customizados
├── .env                 # Variáveis de ambiente
└── composer.json
```

## 📝 Comandos CLI

### Console Setup

```php
// bin/console
#!/usr/bin/env php
<?php

use Express\Core\Application;
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use CAFernandes\ExpressPHP\CycleORM\Commands\SchemaCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\MigrateCommand;

require_once dirname(__DIR__) . '/vendor/autoload.php';

chdir(dirname(__DIR__));

$app = new Application();
$app->register(new CycleServiceProvider($app));
$container = $app->getContainer();

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'cycle:schema:sync':
        $schemaCommand = new SchemaCommand(['--sync' => true], $container);
        $schemaCommand->handle();
        break;
        
    case 'cycle:migrate':
        $migrateCommand = new MigrateCommand([], $container);
        $migrateCommand->handle();
        break;
        
    case 'help':
    default:
        echo "Available commands:\n";
        echo "  cycle:schema:sync  Sync database schema\n";
        echo "  cycle:migrate      Run migrations\n";
        break;
}
```

### Uso dos Comandos

```bash
# Sincronizar schema do banco
php bin/console cycle:schema:sync

# Executar migrações
php bin/console cycle:migrate

# Ver ajuda
php bin/console help
```

## 🔍 Acessando Serviços ORM

### Via Attributes (Recomendado)

```php
public function example(Request $request): Response
{
    // ORM
    $orm = $request->getAttribute('cycle.orm');
    
    // Entity Manager
    $emHelper = $request->getAttribute('entityManager');
    $em = $emHelper();
    
    // Repository
    $repoHelper = $request->getAttribute('repository');
    $userRepo = $repoHelper(User::class);
    
    // Database
    $db = $request->getAttribute('cycle.db');
    
    // Entity Helper
    $entityHelper = $request->getAttribute('entity');
    $user = $entityHelper(User::class, ['name' => 'John']);
}
```

### Via Container (Alternativa)

```php
public function example(Request $request) use ($app): Response
{
    $container = $app->getContainer();
    
    $orm = $container->get('cycle.orm');
    $em = $container->get('cycle.em');
    $db = $container->get('cycle.database');
    $repository = $container->get('cycle.repository');
}
```

## 🎨 Exemplos Práticos

### CRUD Completo

```php
class UserController
{
    // Listar todos
    public function index(Request $request): Response
    {
        $repoHelper = $request->getAttribute('repository');
        $users = $repoHelper(User::class)->findAll();
        
        return (new Response())->json(['users' => $users]);
    }

    // Buscar por ID
    public function show(Request $request): Response
    {
        $id = (int) $request->getParam('id');
        $repoHelper = $request->getAttribute('repository');
        $user = $repoHelper(User::class)->findByPK($id);
        
        if (!$user) {
            return (new Response())->status(404)->json(['error' => 'Not found']);
        }
        
        return (new Response())->json(['user' => $user]);
    }

    // Criar
    public function store(Request $request): Response
    {
        $data = $request->getBody();
        $entityHelper = $request->getAttribute('entity');
        $emHelper = $request->getAttribute('entityManager');
        
        $user = $entityHelper(User::class, $data);
        $em = $emHelper();
        
        $em->persist($user);
        $em->run();
        
        return (new Response())->status(201)->json(['user' => $user]);
    }

    // Atualizar
    public function update(Request $request): Response
    {
        $id = (int) $request->getParam('id');
        $data = $request->getBody();
        
        $repoHelper = $request->getAttribute('repository');
        $emHelper = $request->getAttribute('entityManager');
        
        $user = $repoHelper(User::class)->findByPK($id);
        if (!$user) {
            return (new Response())->status(404)->json(['error' => 'Not found']);
        }
        
        // Atualiza propriedades
        $user->setName($data['name'] ?? $user->getName());
        $user->setEmail($data['email'] ?? $user->getEmail());
        
        $em = $emHelper();
        $em->persist($user);
        $em->run();
        
        return (new Response())->json(['user' => $user]);
    }

    // Deletar
    public function destroy(Request $request): Response
    {
        $id = (int) $request->getParam('id');
        
        $repoHelper = $request->getAttribute('repository');
        $emHelper = $request->getAttribute('entityManager');
        
        $user = $repoHelper(User::class)->findByPK($id);
        if (!$user) {
            return (new Response())->status(404)->json(['error' => 'Not found']);
        }
        
        $em = $emHelper();
        $em->delete($user);
        $em->run();
        
        return (new Response())->status(204)->json([]);
    }
}
```

### Queries Avançadas

```php
public function search(Request $request): Response
{
    $query = $request->getQuery('q', '');
    $repoHelper = $request->getAttribute('repository');
    $repository = $repoHelper(User::class);
    
    // Busca personalizada
    $users = $repository
        ->select()
        ->where('name', 'LIKE', '%' . $query . '%')
        ->orWhere('email', 'LIKE', '%' . $query . '%')
        ->orderBy('created_at', 'DESC')
        ->limit(20)
        ->fetchAll();
    
    return (new Response())->json(['users' => $users]);
}
```

### Transações

```php
public function bulkCreate(Request $request): Response
{
    $items = $request->getBody()['items'] ?? [];
    $emHelper = $request->getAttribute('entityManager');
    $entityHelper = $request->getAttribute('entity');
    
    $em = $emHelper();
    $created = [];
    
    try {
        // Inicia transação
        $em->getTransaction()->begin();
        
        foreach ($items as $data) {
            $user = $entityHelper(User::class, $data);
            $em->persist($user);
            $created[] = $user;
        }
        
        $em->run();
        $em->getTransaction()->commit();
        
        return (new Response())->json([
            'success' => true,
            'created' => count($created),
            'users' => $created
        ]);
        
    } catch (\Exception $e) {
        $em->getTransaction()->rollback();
        
        return (new Response())->status(500)->json([
            'error' => 'Transaction failed',
            'message' => $e->getMessage()
        ]);
    }
}
```

## ⚠️ Problemas Conhecidos

### CycleMiddleware Original

O CycleMiddleware incluído na extensão tem um problema de design que causa erro de tipo recursivo. Por isso, recomendamos usar o middleware customizado mostrado acima até que seja corrigido em versões futuras.

### Diretório de Entidades

A extensão procura entidades em `app/Entities` e `src/Entities`. Certifique-se de que pelo menos um desses diretórios existe ou configure via `CYCLE_ENTITY_DIRS`.

## 🧪 Testando a Integração

```php
// Endpoint de teste
$app->get('/api/test', function ($request): Response {
    $hasOrm = $request->hasAttribute('cycle.orm');
    $hasEm = $request->hasAttribute('cycle.em');
    $hasDb = $request->hasAttribute('cycle.db');
    $hasRepo = $request->hasAttribute('repository');
    
    return (new Response())->json([
        'integration' => 'working',
        'attributes' => [
            'orm' => $hasOrm,
            'em' => $hasEm,
            'db' => $hasDb,
            'repository_helper' => $hasRepo
        ]
    ]);
});
```

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor, siga os padrões PSR-12 e inclua testes para novas funcionalidades.

## 📄 Licença

Este projeto está licenciado sob a licença MIT.

## 🔗 Links Úteis

- [Express PHP Framework](https://github.com/cafernandes/express-php)
- [Cycle ORM Documentation](https://cycle-orm.dev)
- [PHPStan](https://phpstan.org)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)