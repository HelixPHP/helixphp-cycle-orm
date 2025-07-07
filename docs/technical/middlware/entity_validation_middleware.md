# EntityValidationMiddleware

O `EntityValidationMiddleware` adiciona validação automática de entidades no pipeline do PivotPHP, garantindo integridade dos dados antes de persistir no banco.

## Visão Geral
Esse middleware utiliza reflexão para validar tipos e campos obrigatórios das entidades, reduzindo erros de persistência e facilitando debugging.

## Como funciona
- Cria um wrapper `CycleRequest` para cada requisição.
- Disponibiliza método de validação de entidades.
- Retorna erros detalhados em caso de falha de validação.

## Exemplo de Uso
```php
$app->addMiddleware(EntityValidationMiddleware::class);
```

## Boas Práticas
- Utilize para garantir integridade dos dados antes de persistir entidades.
- Combine com middlewares de autenticação e transação para pipelines seguros.

## Integração
Integre com handlers de erro para respostas automáticas em caso de falha de validação.
