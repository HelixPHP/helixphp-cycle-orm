# Documentação HelixPHP Cycle ORM Extension

## 🚀 Início Rápido
- [**Guia de Integração Completo**](./integration-guide.md) 🆕 - Passo a passo detalhado
- [**Referência Rápida**](./quick-reference.md) 🆕 - Comandos e exemplos práticos
- [Guia Completo](./guia-completo.md) - Do básico ao avançado

## 📚 Guias de Implementação
- [Implementação Básica](./implementions/usage_basic.md)
- [Implementação com Middleware](./implementions/usage_with_middleware.md)
- [Middleware Customizado](./implementions/usage_with_custom_middleware.md)
- [Padrões Valida Conceito](./examples/valida-conceito-patterns.md)

## 🔧 Documentação Técnica

### Core Components
- [Service Provider](./technical/provider.md) - Arquitetura e registro de serviços
- [Repository Factory](./technical/repository.md) - Sistema de repositórios
- [CycleRequest](./technical/http/cycle_request.md) - Request estendido com ORM

### Middleware
- [CycleMiddleware](./technical/middlware/cycle_middleware.md) - Integração principal
- [TransactionMiddleware](./technical/middlware/transaction_middleware.md) - Transações automáticas
- [EntityValidationMiddleware](./technical/middlware/entity_validation_middleware.md) - Validação de entidades

### Comandos CLI
- [Schema Command](./technical/commands/schema_command.md) - Sincronização de schema
- [Migrate Command](./technical/commands/migrate_command.md) - Execução de migrações
- [Status Command](./technical/commands/status_command.md) - Status do banco
- [Entity Command](./technical/commands/entity_command.md) - Gerenciamento de entidades

### Monitoramento e Health
- [Health Check](./technical/heath/cycle_health_check.md) - Verificação de saúde
- [Query Logger](./technical/monitoring/query_logger.md) - Log de queries SQL
- [Performance Profiler](./technical/monitoring/performance_profiler.md) - Profiling de performance
- [Metrics Collector](./technical/monitoring/metrics_collector.md) - Coleta de métricas

### Exceptions
- [Exception Handling](./technical/exceptions/exception_handling.md) - Tratamento de erros
- [CycleORMException](./technical/exceptions/cycle_orm_exception.md) - Exceções do ORM
- [EntityNotFoundException](./technical/exceptions/entity_not_found_exception.md) - Entidade não encontrada

## 🤝 Contribuindo
- [Guia de Contribuição](./contributing/README.md)
- [Padrões de Código](../CONTRIBUTING.md)

## 📋 Recursos Adicionais
- [Changelog](../CHANGELOG.md) - Histórico de mudanças
- [Exemplos](../examples/) - Código de exemplo
- [Testes](../tests/README.md) - Informações sobre testes
