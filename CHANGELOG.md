# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

## [1.0.2] - 2025-07-04
### Adicionado
- **Sistema de Testes Completo**: 68 testes automatizados (Unit, Feature, Database)
- **PHPStan Nível 9**: Zero erros de tipagem estática
- **CycleRequest**: Classe de request estendida com métodos ORM integrados
- **Repository Factory**: Sistema de cache inteligente para repositórios
- **Sistema de Monitoramento**: MetricsCollector, PerformanceProfiler, QueryLogger
- **Middlewares Avançados**: TransactionMiddleware e EntityValidationMiddleware

### Melhorado
- **Type Safety**: Todas as classes agora são 100% tipadas
- **Arquitetura**: Refatoração completa seguindo SOLID principles
- **Documentação**: README atualizado com exemplos práticos
- **Testes**: Cobertura completa das funcionalidades principais
- **Performance**: Cache de repositórios e otimização de queries

### Corrigido
- **PHPStan Issues**: Todos os 245 erros de tipagem foram corrigidos
- **Test Infrastructure**: Base de testes robusta com SQLite in-memory
- **Entity Creation**: Método `entity()` agora popula dados corretamente
- **Schema Registration**: Entidades de teste registradas no ORM

### Técnico
- **Testing**: Excludes complex integration tests by default (`@group integration`)
- **CI/CD Ready**: Configuração preparada para integração contínua
- **PSR-12**: Padrões de código seguidos rigorosamente
- **Monitoring**: Sistema completo de métricas e profiling em produção

## [1.0.0] - 2025-07-04
### Adicionado
- Estrutura inicial da extensão Express PHP Cycle ORM
- Integração com Cycle ORM
- Comandos CLI: migrate, schema, status, entity
- Health check para banco de dados
- Suporte a middlewares customizados
- Documentação técnica inicial
