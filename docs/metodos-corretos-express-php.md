# Métodos Corretos do Express PHP

## Request

### Propriedades Principais
- `$req->params` - Parâmetros de rota (stdClass)
  - Ex: `/users/:id` -> `$req->params->id`
- `$req->query` - Parâmetros da query string (stdClass)
  - Ex: `/users?name=John` -> `$req->query->name`
- `$req->body()` - Retorna o corpo da requisição como stdClass
  - Ex: POST com JSON -> `$req->body()->name`

### Métodos Auxiliares
- `$req->input($key, $default = null)` - Busca valor no body com fallback
- `$req->header($key)` - Obtém header específico
- `$req->getMethod()` - Obtém método HTTP (GET, POST, etc)
- `$req->getUri()` - Obtém URI da requisição

### Atributos Dinâmicos (com Cycle ORM Extension)
- `$req->orm` - Instância do ORM
- `$req->em` - Entity Manager
- `$req->db` - Database
- `$req->repository` - Repository Factory

## Response

### Métodos Principais
- `$res->json($data, $status = 200)` - Resposta JSON
- `$res->send($content, $status = 200)` - Resposta texto
- `$res->status($code)` - Define status HTTP
- `$res->header($key, $value)` - Define header

### Exemplos Corretos

```php
// Rota com parâmetro
$app->get('/users/:id', function ($req, $res) {
    $id = $req->params->id;
    return $res->json(['id' => $id]);
});

// Query string
$app->get('/users', function ($req, $res) {
    $name = $req->query->name ?? 'all';
    $page = (int)($req->query->page ?? 1);
    return $res->json(['filter' => $name, 'page' => $page]);
});

// Body da requisição
$app->post('/users', function ($req, $res) {
    $data = $req->body();
    $name = $data->name;
    $email = $data->email;
    
    // Ou usando input()
    $name = $req->input('name', 'Anonymous');
    
    return $res->json(['created' => true], 201);
});

// Múltiplos parâmetros de rota
$app->get('/users/:userId/posts/:postId', function ($req, $res) {
    $userId = $req->params->userId;
    $postId = $req->params->postId;
    return $res->json(['user' => $userId, 'post' => $postId]);
});
```

## Observações Importantes

1. **Não existe** `getParsedBody()` - use `body()`
2. **Não existe** `getQueryParams()` - use `$req->query`
3. **Não existe** `getAttribute()` - use `$req->params->paramName`
4. **Não existe** `getQueryParam()` - use `$req->query->paramName`
5. **Rotas com parâmetros** usam `:param` não `{param}`
6. **body()** retorna stdClass, não array
7. **query** é uma propriedade stdClass, não um método