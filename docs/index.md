# Documentação Express PHP Cycle ORM Extension

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
- [Service Provider](./techinical/provider.md) - Arquitetura e registro de serviços
- [Repository Factory](./techinical/repository.md) - Sistema de repositórios
- [CycleRequest](./techinical/http/cycle_request.md) - Request estendido com ORM

### Middleware
- [CycleMiddleware](./techinical/middlware/cycle_middleware.md) - Integração principal
- [TransactionMiddleware](./techinical/middlware/transaction_middleware.md) - Transações automáticas
- [EntityValidationMiddleware](./techinical/middlware/entity_validation_middleware.md) - Validação de entidades

### Comandos CLI
- [Schema Command](./techinical/commands/schema_command.md) - Sincronização de schema
- [Migrate Command](./techinical/commands/migrate_command.md) - Execução de migrações
- [Status Command](./techinical/commands/status_command.md) - Status do banco
- [Entity Command](./techinical/commands/entity_command.md) - Gerenciamento de entidades

### Monitoramento e Health
- [Health Check](./techinical/heath/cycle_health_check.md) - Verificação de saúde
- [Query Logger](./techinical/monitoring/query_logger.md) - Log de queries SQL
- [Performance Profiler](./techinical/monitoring/performance_profiler.md) - Profiling de performance
- [Metrics Collector](./techinical/monitoring/metrics_collector.md) - Coleta de métricas

### Exceptions
- [Exception Handling](./techinical/exceptions/exception_handling.md) - Tratamento de erros
- [CycleORMException](./techinical/exceptions/cycle_orm_exception.md) - Exceções do ORM
- [EntityNotFoundException](./techinical/exceptions/entity_not_found_exception.md) - Entidade não encontrada

## 🤝 Contribuindo
- [Guia de Contribuição](./contributing/README.md)
- [Padrões de Código](../CONTRIBUTING.md)

## 📋 Recursos Adicionais
- [Changelog](../CHANGELOG.md) - Histórico de mudanças
- [Exemplos](../examples/) - Código de exemplo
- [Testes](../tests/README.md) - Informações sobre testes
