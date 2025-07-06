# Guia de Integração HelixPHP + Cycle ORM

Este guia detalha como integrar corretamente a HelixPHP Cycle ORM Extension em seu projeto.

## 📋 Pré-requisitos

- PHP 8.1 ou superior
- Composer
- HelixPHP 2.1.1+
- SQLite ou MySQL

## 🚀 Instalação Rápida

```bash
# 1. Criar novo projeto
mkdir meu-projeto && cd meu-projeto

# 2. Instalar dependências
composer require helixphp/core helixphp/core-cycle-orm-extension

# 3. Criar estrutura de diretórios
mkdir -p public src/{Controllers,Entities,Repositories} database app/Entities bin
```

## 🔧 Configuração Passo a Passo

### 1. Arquivo de Entrada (public/index.php)

```php
<?php

declare(strict_types=1);

use Express\Core\Application;
use CAFernandes\HelixPHP\CycleORM\CycleServiceProvider;
use Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// IMPORTANTE: Define o diretório de trabalho
chdir(dirname(__DIR__));

// Carrega variáveis de ambiente (opcional)
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
    // Define valores padrão
    $_ENV['APP_ENV'] = 'development';
    $_ENV['APP_DEBUG'] = 'true';
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = __DIR__ . '/../database/database.sqlite';
    $_ENV['CYCLE_ENTITY_DIRS'] = 'src/Entities';
}

// Cria a aplicação
$app = new Application();

// Configura debug (opcional)
$config = $app->getConfig();
$config->set('app.debug', true);

// Registra o Cycle ORM
$app->register(new CycleServiceProvider($app));

// IMPORTANTE: Middleware customizado (solução para o bug do CycleMiddleware)
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

    // Injeta serviços através de attributes
    $req->setAttribute('cycle.orm', $orm);
    $req->setAttribute('cycle.em', $em);
    $req->setAttribute('cycle.db', $db);
    $req->setAttribute('cycle.repository', $repository);

    // Helper methods
    $req->setAttribute('repository', function(string $entityClass) use ($repository) {
        return $repository->getRepository($entityClass);
    });

    $req->setAttribute('entity', function(string $entityClass, array $data = []) use ($orm) {
        return $orm->make($entityClass, $data);
    });

    $req->setAttribute('entityManager', function() use ($em) {
        return $em;
    });

    $next($req, $res);
});

// Suas rotas aqui...

$app->run();
```

### 2. Arquivo de Ambiente (.env)

```env
# Application
APP_NAME="Meu Projeto"
APP_ENV=development
APP_DEBUG=true

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Para MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=meu_banco
# DB_USERNAME=root
# DB_PASSWORD=senha

# Cycle ORM
CYCLE_ENTITY_DIRS=src/Entities
CYCLE_LOG_QUERIES=true
CYCLE_PROFILE_QUERIES=true
```

### 3. Configuração do Composer (composer.json)

```json
{
    "name": "meu/projeto",
    "type": "project",
    "require": {
        "php": "^8.1",
        "helixphp/core": "^2.1.1",
        "helixphp/core-cycle-orm-extension": "^1.0.2",
        "vlucas/phpdotenv": "^5.6"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "scripts": {
        "serve": "php -S localhost:8000 -t public public/index.php"
    }
}
```

## 📝 Criando Entidades

### Exemplo: Entidade User

```php
// src/Entities/User.php
<?php

declare(strict_types=1);

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

    #[Column(type: 'string', nullable: false)]
    private string $password;

    #[Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    // Setters
    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    // Conversão
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

## 🎮 Criando Controllers

### Controller Básico

```php
// src/Controllers/UserController.php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Entities\User;
use Express\Http\Request;
use Express\Http\Response;

class UserController
{
    public function index(Request $request): Response
    {
        try {
            // Obtém o helper de repositório
            $repositoryHelper = $request->getAttribute('repository');
            $repository = $repositoryHelper(User::class);
            
            // Busca todos os usuários
            $users = $repository->findAll();
            
            // Converte para array
            $userData = array_map(fn(User $user) => $user->toArray(), $users);
            
            return (new Response())->json([
                'success' => true,
                'data' => $userData,
                'count' => count($userData)
            ]);
        } catch (\Exception $e) {
            return (new Response())->status(500)->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function store(Request $request): Response
    {
        try {
            $data = $request->getBody();
            
            // Validação básica
            if (!isset($data['name'], $data['email'], $data['password'])) {
                return (new Response())->status(400)->json([
                    'success' => false,
                    'error' => 'Name, email and password are required'
                ]);
            }
            
            // Obtém helpers
            $repositoryHelper = $request->getAttribute('repository');
            $entityManagerHelper = $request->getAttribute('entityManager');
            
            $repository = $repositoryHelper(User::class);
            $entityManager = $entityManagerHelper();
            
            // Verifica se email já existe
            if ($repository->findOne(['email' => $data['email']])) {
                return (new Response())->status(409)->json([
                    'success' => false,
                    'error' => 'Email already exists'
                ]);
            }
            
            // Cria novo usuário
            $user = new User();
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setPassword($data['password']);
            
            // Persiste no banco
            $entityManager->persist($user);
            $entityManager->run();
            
            return (new Response())->status(201)->json([
                'success' => true,
                'data' => $user->toArray()
            ]);
        } catch (\Exception $e) {
            return (new Response())->status(500)->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

## 🛣️ Definindo Rotas

```php
// public/index.php (continuação)

// Instancia controllers
$userController = new \App\Controllers\UserController();

// Define rotas
$app->get('/', function ($req, $res) {
    return $res->json([
        'message' => 'API funcionando!',
        'version' => '1.0.0'
    ]);
});

// Rotas de usuários
$app->get('/api/users', [$userController, 'index']);
$app->get('/api/users/{id}', [$userController, 'show']);
$app->post('/api/users', [$userController, 'store']);
$app->put('/api/users/{id}', [$userController, 'update']);
$app->delete('/api/users/{id}', [$userController, 'destroy']);

// Health check
$app->get('/health', function ($req, $res) {
    $hasOrm = $req->hasAttribute('cycle.orm');
    
    return $res->json([
        'status' => 'healthy',
        'cycle_orm' => $hasOrm ? 'connected' : 'disconnected',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

$app->run();
```

## 🔨 Comandos CLI

### Configurar Console (bin/console)

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

use Express\Core\Application;
use CAFernandes\HelixPHP\CycleORM\CycleServiceProvider;
use CAFernandes\HelixPHP\CycleORM\Commands\SchemaCommand;
use CAFernandes\HelixPHP\CycleORM\Commands\MigrateCommand;
use CAFernandes\HelixPHP\CycleORM\Commands\StatusCommand;

require_once dirname(__DIR__) . '/vendor/autoload.php';

chdir(dirname(__DIR__));

// Configuração
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = __DIR__ . '/../database/database.sqlite';
$_ENV['CYCLE_ENTITY_DIRS'] = 'src/Entities';

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
        
    case 'cycle:status':
        $statusCommand = new StatusCommand([], $container);
        $statusCommand->handle();
        break;
        
    case 'help':
    default:
        echo "Available commands:\n";
        echo "  cycle:schema:sync  Sync database schema\n";
        echo "  cycle:migrate      Run migrations\n";
        echo "  cycle:status       Check migration status\n";
        echo "  help              Show this help message\n";
        break;
}
```

Torne executável:

```bash
chmod +x bin/console
```

### Usando os Comandos

```bash
# Criar/atualizar schema do banco
php bin/console cycle:schema:sync

# Verificar status
php bin/console cycle:status

# Executar migrações
php bin/console cycle:migrate
```

## 🚀 Executando o Projeto

```bash
# 1. Instalar dependências
composer install

# 2. Criar banco de dados
mkdir -p database
touch database/database.sqlite

# 3. Sincronizar schema
php bin/console cycle:schema:sync

# 4. Iniciar servidor
composer serve
# ou
php -S localhost:8000 -t public public/index.php

# 5. Testar
curl http://localhost:8000/health
```

## 🎯 Padrões de Uso

### Acessando Serviços ORM

```php
// Em qualquer rota ou controller:

// 1. Via helpers (recomendado)
$repositoryHelper = $request->getAttribute('repository');
$userRepo = $repositoryHelper(User::class);

// 2. Via attributes diretos
$orm = $request->getAttribute('cycle.orm');
$em = $request->getAttribute('cycle.em');
$db = $request->getAttribute('cycle.db');

// 3. Via container (alternativa)
$container = $app->getContainer();
$orm = $container->get('cycle.orm');
```

### Transações

```php
public function bulkOperation(Request $request): Response
{
    $emHelper = $request->getAttribute('entityManager');
    $em = $emHelper();
    
    try {
        $em->getTransaction()->begin();
        
        // Operações múltiplas
        foreach ($items as $item) {
            $entity = new Entity();
            // ...
            $em->persist($entity);
        }
        
        $em->run();
        $em->getTransaction()->commit();
        
        return (new Response())->json(['success' => true]);
        
    } catch (\Exception $e) {
        $em->getTransaction()->rollback();
        return (new Response())->status(500)->json(['error' => $e->getMessage()]);
    }
}
```

## ⚠️ Troubleshooting

### Erro: "The directory does not exist"

**Solução**: Crie o diretório `app/Entities`:

```bash
mkdir -p app/Entities
```

### Erro: "Cycle ORM not properly registered"

**Solução**: Certifique-se de registrar o provider antes de usar:

```php
$app->register(new CycleServiceProvider($app));
```

### Erro com CycleMiddleware original

**Solução**: Use o middleware customizado mostrado neste guia em vez do CycleMiddleware incluído.

## 📚 Recursos Adicionais

- [Documentação do HelixPHP](https://github.com/helixphp/core)
- [Documentação do Cycle ORM](https://cycle-orm.dev)
- [Exemplos de código](https://github.com/helixphp/core-cycle-orm-extension/tree/main/examples)

## 🤝 Suporte

Se encontrar problemas:

1. Verifique os logs em `storage/logs/`
2. Ative o debug: `APP_DEBUG=true`
3. Abra uma issue no [GitHub](https://github.com/helixphp/core-cycle-orm-extension/issues)