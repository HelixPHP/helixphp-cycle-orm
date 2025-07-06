# Guia Técnico: CycleServiceProvider

O `CycleServiceProvider` é o principal ponto de integração entre o Express PHP e o Cycle ORM. Ele gerencia o ciclo de vida dos serviços relacionados ao ORM, banco de dados, middlewares e comandos CLI.

## Propriedades e Responsabilidades
- **Application $app**: Instância principal da aplicação Express PHP.

## Métodos Públicos
- `__construct(Application $app)`: Inicializa o provider e inclui helpers necessários.
- `register()`: Registra todos os serviços essenciais (DatabaseManager, Schema, ORM, EntityManager, RepositoryFactory, Migrator).
- `boot()`: Realiza o boot dos middlewares, comandos e ativa recursos de desenvolvimento se necessário.

## Métodos Protegidos/Privados Importantes
- `ensureCallableHandler($handler)`: Garante que o handler de rota seja sempre callable.
- `includeHelpers()`: Inclui helpers globais para ambiente e paths.
- `registerDatabaseManager()`: Registra o gerenciador de banco de dados.
- `registerSchemaCompiler()`: Compila e registra o schema do Cycle ORM.
- (Outros métodos privados para registro de ORM, EntityManager, RepositoryFactory, Migrator, Middlewares e Commands)

## Boas Práticas
- Sempre utilize o provider para registrar dependências do Cycle ORM.
- Utilize variáveis de ambiente para controle de debug e ambiente.
- Utilize o método `ensureCallableHandler` ao registrar rotas para evitar erros de callable.

## Exemplo de Uso
```php
$app->register(new CycleServiceProvider($app));
```

Consulte a documentação dos comandos, middlewares e exceptions para detalhes de cada integração.
