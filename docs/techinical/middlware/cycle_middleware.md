# CycleMiddleware

O `CycleMiddleware` integra o Cycle ORM ao pipeline de requisições do Express PHP, tornando o ORM, EntityManager e Database acessíveis em todas as rotas.

## Visão Geral
Esse middleware é essencial para garantir que todas as requisições tenham acesso ao contexto do ORM, facilitando operações de persistência, consulta e transação.

## Como funciona
- Injeta instâncias de ORM, EntityManager e Database no objeto `CycleRequest`.
- Garante que todas as rotas tenham acesso ao ORM de forma transparente.
- Lança exceção se o ORM não estiver registrado corretamente.

## Exemplo de Uso
```php
$app->addMiddleware(CycleMiddleware::class);
```

## Boas Práticas
- Adicione este middleware antes de outros que dependam do ORM.
- Documente a ordem dos middlewares para evitar conflitos.

## Integração
Combine com middlewares de autenticação, validação e transação para pipelines completos.
