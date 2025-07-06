# Guia de Integração com Middleware

Este guia mostra como integrar middlewares do HelixPHP com o Cycle ORM Extension.

## O que são Middlewares?
Middlewares permitem interceptar e manipular requisições e respostas.

## Como Usar
1. Registre o middleware no pipeline do HelixPHP:
```php
$app->addMiddleware(CycleMiddleware::class);
```
2. O middleware pode acessar o container, entidades e repositórios.

## Exemplo
```php
class CycleMiddleware {
    public function process($request, $handler) {
        // lógica customizada
        return $handler->handle($request);
    }
}
```

Consulte o guia de middlewares customizados para exemplos avançados.
