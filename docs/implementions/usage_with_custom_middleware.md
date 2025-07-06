# Guia: Criando um Middleware Personalizado

Aprenda a criar middlewares customizados para integração com o Cycle ORM Extension.

## Passos
1. Crie uma classe implementando a interface de middleware do HelixPHP.
2. Injete dependências necessárias via construtor.
3. Implemente a lógica desejada (ex: logging, autenticação, etc).

## Exemplo
```php
class CustomCycleMiddleware {
    public function process($request, $handler) {
        // Exemplo: log antes de processar
        // ...
        return $handler->handle($request);
    }
}
```

## Registro
Adicione seu middleware ao pipeline:
```php
$app->addMiddleware(CustomCycleMiddleware::class);
```

Consulte a documentação técnica para integração avançada.
