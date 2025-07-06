# Guia de Integração Completo

Este guia fornece instruções detalhadas para integrar o Express PHP Cycle ORM Extension em seu projeto.

## Índice

1. [Requisitos](#requisitos)
2. [Instalação](#instalação)
3. [Configuração Inicial](#configuração-inicial)
4. [Estrutura de Diretórios](#estrutura-de-diretórios)
5. [Uso do CycleMiddleware](#uso-do-cyclemiddleware)
6. [Padrões de Implementação](#padrões-de-implementação)
7. [Resolução de Problemas](#resolução-de-problemas)
8. [Exemplos Práticos](#exemplos-práticos)

## Requisitos

- PHP 8.1 ou superior (PHP 8.3 recomendado para evitar avisos de depreciação)
- Composer 2.0+
- Express PHP Framework 2.1+
- SQLite ou MySQL

## Instalação

```bash
composer require cafernandes/express-php-cycle-orm-extension
```

## Configuração Inicial

### 1. Estrutura de Diretórios

Crie a seguinte estrutura em seu projeto:

```
seu-projeto/
├── app/
│   └── Entities/        # Diretório para entidades (obrigatório)
├── database/
│   └── database.sqlite  # Arquivo de banco SQLite
├── public/
│   └── index.php       # Ponto de entrada
└── composer.json
```

### 2. Configuração Básica

```php
<?php
// public/index.php

// IMPORTANTE: Define o diretório de trabalho
chdir(dirname(__DIR__));

require_once __DIR__ . '/../vendor/autoload.php';

use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use Express\Core\Application;

// Configuração do ambiente
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = __DIR__ . '/../database/database.sqlite';

// Criar aplicação
$app = new Application();

// Registrar o Cycle ORM
$app->register(new CycleServiceProvider($app));

// Suas rotas aqui...

$app->run();
```

### 3. Variáveis de Ambiente

Configure as variáveis de ambiente conforme seu banco de dados:

#### SQLite (Desenvolvimento)
```env
DB_CONNECTION=sqlite
DB_DATABASE=./database/database.sqlite
```

#### MySQL (Produção)
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=seu_banco
DB_USERNAME=usuario
DB_PASSWORD=senha
```

## Uso do CycleMiddleware

O CycleMiddleware transforma automaticamente requisições Express em CycleRequest, fornecendo acesso direto aos serviços ORM.

### Configuração do Middleware

```php
use CAFernandes\ExpressPHP\CycleORM\Middleware\CycleMiddleware;

// Adicionar o middleware globalmente
$app->use(new CycleMiddleware($app));

// Agora todas as rotas recebem CycleRequest
$app->get('/users', function ($req, $res) {
    // $req é agora um CycleRequest com métodos ORM
    $users = $req->repository(User::class)->findAll();
    return $res->json($users);
});
```

### Métodos Disponíveis no CycleRequest

```php
// Repositório para entidade
$userRepo = $req->repository(User::class);

// Criar nova entidade
$user = $req->entity(User::class, ['name' => 'John']);

// Paginação
$paginated = $req->paginate(User::class, 20, 1);

// Acesso direto aos serviços (via container)
$orm = $req->getContainer()->get('cycle.orm');
$em = $req->getContainer()->get('cycle.em');
$db = $req->getContainer()->get('cycle.database');
```

## Padrões de Implementação

### 1. Implementação Básica (MVP/Prototipagem)

```php
// Acesso direto ao banco de dados
$app->get('/api/users', function ($req, $res) use ($app) {
    $db = $app->make('cycle.database');
    $users = $db->database()
        ->query('SELECT * FROM users')
        ->fetchAll();
    
    return $res->json(['data' => $users]);
});

// Inserção simples
$app->post('/api/users', function ($req, $res) use ($app) {
    $db = $app->make('cycle.database');
    $data = $req->body;
    
    $db->database()->execute(
        'INSERT INTO users (name, email) VALUES (?, ?)',
        [$data->name, $data->email]
    );
    
    return $res->json(['message' => 'User created'], 201);
});
```

### 2. Implementação com CycleRequest

```php
// Com CycleMiddleware ativo
$app->get('/api/users', function ($req, $res) {
    // Acesso via container do CycleRequest
    $db = $req->getContainer()->get('cycle.database');
    
    $users = $db->database()
        ->query('SELECT * FROM users ORDER BY id DESC')
        ->fetchAll();
    
    return $res->json([
        'status' => true,
        'data' => $users,
        'meta' => [
            'request_type' => get_class($req), // CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest
            'total' => count($users)
        ]
    ]);
});
```

### 3. Implementação com Entidades

```php
// app/Entities/User.php
namespace App\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table('users')]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $email;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $createdAt = null;
}
```

### 4. Implementação com Repository Pattern

```php
// Infrastructure/Repositories/UserRepository.php
namespace Infrastructure\Repositories;

use Cycle\Database\DatabaseInterface;

class UserRepository
{
    public function __construct(
        private DatabaseInterface $database
    ) {}
    
    public function findAll(): array
    {
        return $this->database->database()
            ->query('SELECT * FROM users ORDER BY id DESC')
            ->fetchAll();
    }
    
    public function findById(int $id): ?array
    {
        return $this->database->database()
            ->query('SELECT * FROM users WHERE id = ?', [$id])
            ->fetch();
    }
    
    public function create(array $data): int
    {
        $this->database->database()->execute(
            'INSERT INTO users (name, email, createdAt) VALUES (?, ?, ?)',
            [$data['name'], $data['email'], date('Y-m-d H:i:s')]
        );
        
        return (int) $this->database->database()
            ->getDriver()
            ->lastInsertID();
    }
}

// Registrar no container
$app->getContainer()->bind(UserRepository::class, function ($container) {
    return new UserRepository(
        $container->get('cycle.database')
    );
});

// Usar em rotas
$app->get('/api/users', function ($req, $res) use ($app) {
    $repository = $app->make(UserRepository::class);
    $users = $repository->findAll();
    
    return $res->json(['data' => $users]);
});
```

## Resolução de Problemas

### PHP 8.4 - Avisos de Depreciação

**Problema**: Avisos sobre parâmetros implicitamente nullable no Spiral Core.

**Soluções**:

1. **Usar PHP 8.1 ou 8.3** (Recomendado)
   ```bash
   php8.1 -S localhost:8000 public/index.php
   ```

2. **Suprimir avisos de depreciação** (Não recomendado para produção)
   ```bash
   php -d error_reporting=E_ALL~E_DEPRECATED -S localhost:8000 public/index.php
   ```

### Diretório de Entidades Não Encontrado

**Problema**: Erro "The app/Entities directory does not exist"

**Solução**: 
1. Certifique-se de que o diretório `app/Entities` existe
2. Defina o diretório de trabalho correto no início do script:
   ```php
   chdir(dirname(__DIR__)); // Define o diretório raiz do projeto
   ```

### Múltiplos Middlewares

**Problema**: Erro de tipo quando CycleMiddleware é registrado múltiplas vezes.

**Solução**: Registre o CycleMiddleware apenas uma vez, preferencialmente de forma global:
```php
// Correto
$app->use(new CycleMiddleware($app));

// Evite registrar novamente em rotas individuais
```

## Exemplos Práticos

### API CRUD Completa

```php
<?php
// public/index.php

chdir(dirname(__DIR__));
require_once __DIR__ . '/../vendor/autoload.php';

use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use CAFernandes\ExpressPHP\CycleORM\Middleware\CycleMiddleware;
use Express\Core\Application;

$app = new Application();

// Configuração
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = __DIR__ . '/../database/database.sqlite';

// Registrar serviços
$app->register(new CycleServiceProvider($app));
$app->use(new CycleMiddleware($app));

// Rotas
$app->get('/', function ($req, $res) {
    return $res->json([
        'message' => 'API with Cycle ORM',
        'endpoints' => [
            'GET /api/users' => 'List users',
            'POST /api/users' => 'Create user',
            'GET /api/users/{id}' => 'Get user',
            'PUT /api/users/{id}' => 'Update user',
            'DELETE /api/users/{id}' => 'Delete user'
        ]
    ]);
});

// Setup do banco
$app->get('/setup', function ($req, $res) {
    $db = $req->getContainer()->get('cycle.database');
    
    try {
        $db->database()->execute("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        return $res->json(['message' => 'Database setup completed']);
    } catch (Exception $e) {
        return $res->json(['error' => $e->getMessage()], 500);
    }
});

// Listar usuários
$app->get('/api/users', function ($req, $res) {
    $db = $req->getContainer()->get('cycle.database');
    $users = $db->database()->query('SELECT * FROM users')->fetchAll();
    
    return $res->json(['data' => $users]);
});

// Criar usuário
$app->post('/api/users', function ($req, $res) {
    $db = $req->getContainer()->get('cycle.database');
    $data = $req->body;
    
    if (empty($data->name) || empty($data->email)) {
        return $res->json(['error' => 'Name and email required'], 400);
    }
    
    try {
        $db->database()->execute(
            'INSERT INTO users (name, email) VALUES (?, ?)',
            [$data->name, $data->email]
        );
        
        $id = $db->database()->getDriver()->lastInsertID();
        $user = $db->database()
            ->query('SELECT * FROM users WHERE id = ?', [$id])
            ->fetch();
        
        return $res->json(['data' => $user], 201);
    } catch (Exception $e) {
        return $res->json(['error' => 'Email already exists'], 409);
    }
});

// Buscar usuário
$app->get('/api/users/:id', function ($req, $res) {
    $db = $req->getContainer()->get('cycle.database');
    $id = $req->params->id;
    
    $user = $db->database()
        ->query('SELECT * FROM users WHERE id = ?', [$id])
        ->fetch();
    
    if (!$user) {
        return $res->json(['error' => 'User not found'], 404);
    }
    
    return $res->json(['data' => $user]);
});

// Atualizar usuário
$app->put('/api/users/:id', function ($req, $res) {
    $db = $req->getContainer()->get('cycle.database');
    $id = $req->params->id;
    $data = $req->body;
    
    $user = $db->database()
        ->query('SELECT * FROM users WHERE id = ?', [$id])
        ->fetch();
    
    if (!$user) {
        return $res->json(['error' => 'User not found'], 404);
    }
    
    $name = $data->name ?? $user['name'];
    $email = $data->email ?? $user['email'];
    
    try {
        $db->database()->execute(
            'UPDATE users SET name = ?, email = ? WHERE id = ?',
            [$name, $email, $id]
        );
        
        $updated = $db->database()
            ->query('SELECT * FROM users WHERE id = ?', [$id])
            ->fetch();
        
        return $res->json(['data' => $updated]);
    } catch (Exception $e) {
        return $res->json(['error' => 'Email already exists'], 409);
    }
});

// Deletar usuário
$app->delete('/api/users/:id', function ($req, $res) {
    $db = $req->getContainer()->get('cycle.database');
    $id = $req->params->id;
    
    $user = $db->database()
        ->query('SELECT * FROM users WHERE id = ?', [$id])
        ->fetch();
    
    if (!$user) {
        return $res->json(['error' => 'User not found'], 404);
    }
    
    $db->database()->execute('DELETE FROM users WHERE id = ?', [$id]);
    
    return $res->json(['message' => 'User deleted'], 204);
});

$app->run();
```

### Executando o Projeto

1. **Criar estrutura de diretórios**:
   ```bash
   mkdir -p app/Entities database
   touch database/database.sqlite
   ```

2. **Iniciar servidor (PHP 8.1)**:
   ```bash
   php8.1 -S localhost:8000 public/index.php
   ```

3. **Configurar banco de dados**:
   ```bash
   curl http://localhost:8000/setup
   ```

4. **Testar endpoints**:
   ```bash
   # Listar usuários
   curl http://localhost:8000/api/users
   
   # Criar usuário
   curl -X POST http://localhost:8000/api/users \
     -H "Content-Type: application/json" \
     -d '{"name":"John Doe","email":"john@example.com"}'
   ```

## Melhores Práticas

1. **Sempre defina o diretório de trabalho** no início do script principal
2. **Use PHP 8.1 ou 8.3** para evitar avisos de depreciação
3. **Crie o diretório `app/Entities`** mesmo se não usar entidades anotadas
4. **Registre o CycleMiddleware globalmente** para evitar conflitos
5. **Use transações** para operações múltiplas com TransactionMiddleware
6. **Implemente cache** para melhorar performance em produção

## Suporte

Para problemas ou dúvidas:
- Abra uma issue no [GitHub](https://github.com/cafernandes/express-php-cycle-orm-extension)
- Consulte a [documentação completa](./index.md)
- Veja os [exemplos de implementação](../examples/)