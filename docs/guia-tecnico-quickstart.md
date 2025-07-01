# Guia Técnico e Quick Start — Express-PHP Cycle ORM Extension

## Sumário
- [Visão Geral](#visão-geral)
- [Funcionalidades Principais](#funcionalidades-principais)
- [Quick Start](#quick-start)
- [Recursos Avançados](#recursos-avançados)
- [Dúvidas e Suporte](#dúvidas-e-suporte)

---

## Visão Geral
A extensão Express-PHP Cycle ORM integra o Cycle ORM ao microframework Express-PHP, fornecendo injeção automática de serviços, gerenciamento inteligente de transações, validação de entidades, helpers para queries e arquitetura baseada em middlewares, tudo com zero configuração manual.

## Funcionalidades Principais

### Injeção Automática de Serviços
- `$req->orm`: Instância do Cycle ORM
- `$req->em`: EntityManager para persistência
- `$req->db`: Database Manager
- `$req->repository(EntityClass)`: Repositório da entidade
- `$req->entity(EntityClass, $data)`: Cria entidade populada
- `$req->find(EntityClass, $id)`: Busca por PK
- `$req->paginate($query, $page, $perPage)`: Paginação de resultados
- `$req->validateEntity($entity)`: Validação automática

### Gerenciamento Inteligente de Transações
- Transações abertas automaticamente em cada request
- Commit automático em sucesso, rollback em exceções
- Middleware `TransactionMiddleware` customizável

### Validação de Entidades
- Validação básica via reflection (tipos, campos obrigatórios)
- Pode ser estendida com regras customizadas

### Helpers para Queries
- Filtros dinâmicos, busca textual, ordenação e paginação via helpers
- Eager loading de relacionamentos com `.load('relacao')`

### Arquitetura Middleware-Driven
- Middlewares: `CycleMiddleware`, `TransactionMiddleware`, `EntityValidationMiddleware`, `HealthCheckMiddleware`
- Compatível com qualquer stack de middlewares do Express-PHP

### CLI Integrada
- Comandos: `make:entity`, `cycle:schema --sync`, `cycle:status`, `migrate`, `seed` etc.

### Zero Configuração
- Auto-discovery: basta instalar, sem necessidade de registrar providers manualmente
- Configuração mínima via `.env` e `config/cycle.php`

### Compatibilidade
- 100% compatível com handlers e middlewares Express-PHP
- Proxy transparente: `CycleRequest` expõe todos métodos do `Request` original

### Exemplos Avançados
- CRUD completo, queries com joins, subqueries, operações em lote, analytics, health check, etc.
- Veja exemplos em `examples/basic-usage.php` e `examples/advanced-queries.php`

---

## Quick Start

### 1. Instalação
```bash
composer require cafernandes/express-php-cycle-orm-extension
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
```

### 3. Gerar Primeira Entidade
```bash
php express make:entity User
```

### 4. Sincronizar o Schema
```bash
php express cycle:schema --sync
```

### 5. Implementação Básica de API
Exemplo de `public/index.php`:
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

---

## Recursos Avançados
- Paginação: `$req->paginate($query, $page, $perPage)`
- Filtros: `CycleHelpers::applyFilters($query, $filters, $campos)`
- Busca textual: `CycleHelpers::applySearch($query, $search, $campos)`
- Eager loading: `$query->load('relacao')`
- Validação: `$req->validateEntity($entity)`

---

## Dúvidas e Suporte
- Documentação: [docs.express-php.dev/cycle-orm](https://docs.express-php.dev/cycle-orm)
<!-- - Discord: [express-php.dev/discord](https://express-php.dev/discord) -->
- Issues: [github.com/express-php/cycle-orm-extension/issues](https://github.com/express-php/cycle-orm-extension/issues)
