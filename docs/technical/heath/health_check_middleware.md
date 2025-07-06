# HealthCheckMiddleware

O `HealthCheckMiddleware` expõe um endpoint de health check para o Cycle ORM, permitindo monitoramento automatizado da saúde da aplicação.

## Visão Geral
Intercepta requisições para `/health` ou `/health/cycle` e retorna informações sobre o status do ORM, banco de dados e outros serviços.

## Como funciona
- Retorna status HTTP 200 (saudável) ou 503 (falha).
- Suporta resposta detalhada via query string `?detailed=1`.
- Pode ser customizado para incluir outros checks.

## Exemplo de Uso
```php
$app->addMiddleware(HealthCheckMiddleware::class);
```

## Boas Práticas
- Utilize em ambientes de produção para monitoramento automatizado.
- Integre com sistemas de monitoramento externos (ex: UptimeRobot, Prometheus).

## Integração
Combine com outros middlewares de health check para cobertura completa da aplicação.
