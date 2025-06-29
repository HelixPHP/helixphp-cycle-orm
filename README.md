# Express-PHP Cycle ORM Extension

Integração completa do Cycle ORM com o microframework Express-PHP, mantendo a filosofia ultraleve e performance excepcional.

## ⚡ Características

- **Auto-Discovery**: Registra automaticamente via Service Provider
- **Middleware Integration**: Injeção automática de ORM, EntityManager e helpers
- **Transaction Management**: Transações automáticas com middleware
- **CLI Commands**: Comandos para schema, migrações e geração de entidades
- **Zero Configuration**: Funciona out-of-the-box com configurações sensatas
- **High Performance**: Otimizado para microframework ultraleve

## 🚀 Instalação

```bash
composer require express-php/cycle-orm-extension
```

O Service Provider é registrado automaticamente via auto-discovery.

## ⚙️ Configuração

Publique o arquivo de configuração (opcional):

```bash
php express vendor:publish --provider="ExpressPHP\CycleORM\CycleServiceProvider"
```

Configure suas variáveis de ambiente:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=express_db
DB_USERNAME=root
DB_PASSWORD=
```

## 📖 Uso Básico

### Criando uma Entidade

```bash
php express make:entity User
```

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
}
```

### Usando nas Rotas

```php
$app->get('/api/users', function($req, $res) {
    // Repository injetado automaticamente
    $users = $req->repository(User::class)->findAll();
    $res->json(['users' => $users]);
});

$app->post('/api/users', function($req, $res) {
    // Entity helper
    $user = $req->entity(User::class, $req->body);

    // EntityManager injetado
    $req->em->persist($user);
    // Auto-commit via TransactionMiddleware

    $res->status(201)->json(['user' => $user]);
});
```

### Comandos CLI

```bash
# Sincronizar schema
php express cycle:schema --sync

# Executar migrações
php express cycle:migrate

# Gerar entidade
php express make:entity Post
```

## 🛠️ Middlewares Disponíveis

### CycleMiddleware
Injeta automaticamente ORM, EntityManager e helpers no request.

### TransactionMiddleware
Gerencia transações automaticamente:

```php
$app->post('/api/users', function($req, $res) {
    // Transação iniciada automaticamente
    $user = new User($req->body);
    $req->em->persist($user);
    // Auto-commit ao final (ou rollback em caso de erro)
});
```

## 🎯 Recursos Avançados

### Paginação e Filtros

```php
use ExpressPHP\CycleORM\Helpers\CycleHelpers;

$app->get('/api/users', function($req, $res) {
    $query = $req->repository(User::class)->select();

    // Filtros dinâmicos
    $filters = $req->query['filters'] ?? [];
    $query = CycleHelpers::applyFilters($query, $filters);

    // Ordenação
    $query = CycleHelpers::applySorting($query, 'createdAt', 'desc');

    // Paginação
    $result = CycleHelpers::paginate($query, $req->query['page'] ?? 1);

    $res->json($result);
});
```

### Relacionamentos Complexos

```php
$app->get('/api/users/:id', function($req, $res) {
    $user = $req->repository(User::class)
        ->select()
        ->load('posts', [
            'method' => \Cycle\ORM\Select::SINGLE_QUERY
        ])
        ->where('id', $req->params['id'])
        ->fetchOne();

    $res->json(['user' => $user]);
});
```

## 🧪 Testing

```bash
composer test
composer test-coverage
composer phpstan
```

## 📊 Performance

- **Zero Overhead**: Registra serviços apenas quando necessário
- **Lazy Loading**: Repositories e connections são lazy-loaded
- **Optimized Queries**: Helpers otimizados para queries comuns
- **Transaction Efficiency**: Transações automáticas evitam commits desnecessários

## 📚 Documentação Completa

Veja a documentação completa em: [docs/cycle-orm-extension.md](docs/cycle-orm-extension.md)

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.