# Tests Structure

## Reorganização da Suíte de Testes

Esta nova estrutura de testes foi criada para ser mais próxima da realidade e usar o mínimo de mocks possível.

### Estrutura

- **`tests/legacy/`** - Testes antigos movidos para preservar o histórico
- **`tests/Unit/`** - Testes unitários com configuração real do framework
- **`tests/Feature/`** - Testes de funcionalidades específicas
- **`tests/Integration/`** - Testes de integração completa com HelixPHP
- **`tests/Database/`** - Testes específicos de operações de banco de dados
- **`tests/Entities/`** - Entidades de teste reais
- **`tests/Mocks/`** - Mocks mínimos necessários

### Comandos de Teste

```bash
# Todos os testes
composer test

# Por categoria
composer test:unit
composer test:feature
composer test:integration
composer test:database
composer test:legacy

# Com coverage
composer test-coverage
```

### Implementação

Os novos testes utilizam:

1. **Banco SQLite em memória** para testes rápidos e isolados
2. **Entidades reais** (User, Post) com relacionamentos
3. **Configuração real** do Cycle ORM
4. **Integração real** com HelixPHP
5. **Mocks mínimos** apenas onde absolutamente necessário

### Limitações Atuais

Devido à complexidade da integração entre HelixPHP e Cycle ORM, alguns testes requerem mais trabalho para configuração completa. A versão atual fornece uma base sólida que pode ser expandida.

### Benefícios

- Testes mais próximos do uso real
- Detecção de problemas de integração
- Documentação por meio de exemplos
- Facilita debugging de problemas
- Base para desenvolvimento futuro

### Próximos Passos

Para completar a implementação dos testes, seria necessário:

1. Resolver compatibilidades entre versões do HelixPHP
2. Implementar mocks mais sofisticados se necessário
3. Adicionar testes de performance
4. Testes de middleware em contexto real
5. Testes de CLI commands

Esta reorganização mantém todos os testes antigos funcionais na pasta `legacy` e introduz uma abordagem mais moderna e realista para testes futuros.