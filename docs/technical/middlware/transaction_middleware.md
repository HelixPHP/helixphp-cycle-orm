# TransactionMiddleware

O `TransactionMiddleware` gerencia transações do Cycle ORM durante o ciclo de vida da requisição, garantindo atomicidade e consistência das operações no banco de dados.

## Visão Geral
Inicia uma transação no início da requisição, faz commit ao final (caso não haja erros) e rollback em caso de exceções, protegendo a integridade dos dados.

## Como funciona
- Inicia uma transação no início da requisição.
- Faz commit ao final, caso não haja erros.
- Faz rollback em caso de exceções.
- Loga início e fim da transação para auditoria.

## Exemplo de Uso
```php
$app->addMiddleware(TransactionMiddleware::class);
```

## Boas Práticas
- Utilize em rotas que realizam operações de escrita no banco de dados.
- Combine com middlewares de validação e autenticação para pipelines robustos.

## Integração
O `TransactionMiddleware` pode ser integrado a sistemas de logging e monitoramento para rastreamento de transações.
