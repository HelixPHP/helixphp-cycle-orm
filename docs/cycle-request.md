# Integração Cycle ORM Extension para Express-PHP

> Consulte também o [Guia Técnico e Quick Start](./guia-tecnico-quickstart.md) para visão geral, exemplos e integração rápida.

## Visão Geral
A extensão injeta recursos do Cycle ORM no seu projeto Express-PHP de forma **opcional, segura e sem acoplamento** ao core do framework, utilizando um wrapper inteligente: `CycleRequest`.

---

## Como funciona
- O middleware `CycleMiddleware` converte automaticamente o objeto `Request` em um `CycleRequest` ao passar pela rota.
- O `CycleRequest` expõe métodos e propriedades do Cycle ORM, mantendo 100% de compatibilidade com o `Express\Http\Request`.
- Você pode usar type hint, duck typing, union types ou o helper `cycle()` para acessar os recursos do ORM.

---

## Exemplos de Uso

### 1. Type Hint Específico (Autocomplete Total)
```php
use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;
use Express\Http\Response;

$app->get('/users', function(CycleRequest $req, Response $res) {
    $users = $req->repository(User::class)->findAll();
    $res->json(['users' => $users]);
});
```

### 2. Duck Typing com Comentário
```php
$app->get('/users', function($req, $res) {
    /** @var \\CAFernandes\\ExpressPHP\\CycleORM\\Http\\CycleRequest $req */
    $users = $req->repository(User::class)->findAll();
    $res->json(['users' => $users]);
});
```

### 3. Union Type (PHP 8+)
```php
$app->get('/users', function(Request|CycleRequest $req, Response $res) {
    if ($req instanceof CycleRequest) {
        $users = $req->repository(User::class)->findAll();
        $res->json(['users' => $users]);
    }
});
```

### 4. Helper para Garantir CycleRequest
```php
use function CAFernandes\ExpressPHP\CycleORM\Helpers\cycle;

$app->get('/users', function(Request $req, Response $res) {
    $users = cycle($req)->repository(User::class)->findAll();
    $res->json(['users' => $users]);
});
```

---

## Métodos Disponíveis em `CycleRequest`
- `$req->orm` — Instância do ORM
- `$req->em` — EntityManager
- `$req->db` — Database
- `$req->repository(string $entityClass): RepositoryInterface`
- `$req->entity(string $entityClass, array $data): object`
- `$req->find(string $entityClass, mixed $id): ?object`
- `$req->paginate(Select $query, int $page = 1, int $perPage = 15): array`
- `$req->validateEntity(object $entity, array $rules = []): array`

---

## Compatibilidade
- **Totalmente compatível** com middlewares e handlers existentes.
- O objeto `CycleRequest` faz proxy de todos os métodos e propriedades do `Request` original.
- Não há dependência obrigatória no core do Express-PHP.

---

## Observações
- Sempre adicione o `CycleMiddleware` na stack de middlewares para garantir a injeção do wrapper.
- Se o handler não receber um `CycleRequest`, use o helper `cycle($req)` para garantir acesso aos recursos do ORM.

---

## Exemplo de Stack
```php
$app->use(new \\CAFernandes\\ExpressPHP\\CycleORM\\Middleware\\CycleMiddleware($app));
```

---

## Dúvidas?
Consulte a documentação oficial do Cycle ORM ou abra uma issue no repositório da extensão.
