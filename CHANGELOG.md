# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.1.0] - 2025-01-06

### Adicionado
- **Guia de Integração Completo**: Nova documentação detalhada em `docs/integration-guide.md`
- **Compatibilidade PHP 8.4**: Documentação sobre avisos de depreciação e soluções
- **Badge PSR-12**: Indicador de conformidade com padrões no README
- **Exemplos CRUD**: Implementação completa de API REST com todos os verbos HTTP
- **Troubleshooting**: Seção dedicada para resolução de problemas comuns

### Alterado
- **CycleMiddleware**: Agora cria corretamente o wrapper CycleRequest antes de passar para o próximo handler
- **QueryLogger**: Método `clear()` renomeado para `clearLogs()` (mantendo alias para retrocompatibilidade)
- **Documentação**: README atualizado com instruções claras sobre `chdir()` e estrutura de diretórios
- **GitHub Actions**: Workflow CI atualizado para refletir comandos do ambiente local

### Corrigido
- **CycleORMException**: Removido 4º parâmetro do construtor (context array)
- **Type Checking**: Alterado de `instanceof ORM` para `instanceof ORMInterface` para maior flexibilidade
- **Table Annotations**: Corrigida sintaxe de anotações nas entidades de teste
- **CycleRequest**: Adicionados métodos `getAttribute()` e `setAttribute()` com tipos corretos

### Removido
- **validateEntity()**: Método stub não utilizado removido do CycleRequest
- **validateDatabaseConfig()** e **validateEntityConfig()**: Métodos stub não utilizados removidos do CycleServiceProvider

### Melhorias de Qualidade
- **PSR-12**: Conformidade total validada com phpcs
- **PHPStan**: Nível 9 sem erros ou avisos
- **Testes**: 68 testes passando com sucesso
- **Documentação**: Guias práticos e exemplos de uso real

## [1.0.2] - 2025-01-04
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
