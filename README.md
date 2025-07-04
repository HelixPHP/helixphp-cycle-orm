# Express PHP Cycle ORM Extension

[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-68%20passing-brightgreen.svg)](https://phpunit.de/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Uma extensão robusta e bem testada que integra o Cycle ORM ao framework Express PHP, oferecendo recursos avançados de ORM com arquitetura limpa e moderna.

## 🚀 Características

- **Integração Completa**: Perfeita integração com Express PHP através de Service Provider
- **Type Safety**: Código 100% tipado com PHPStan nível 9
- **Bem Testado**: 68 testes automatizados cobrindo todas as funcionalidades
- **Repositórios Customizados**: Factory pattern para repositórios com cache inteligente
- **Middlewares Prontos**: Transaction e Entity Validation middlewares
- **Monitoramento**: Sistema completo de métricas e profiling
- **CycleRequest**: Extensão intuitiva do Request com métodos ORM
- **CLI Commands**: Comandos para migração e gerenciamento do schema

## 📦 Instalação

```bash
composer require cafernandes/express-php-cycle-orm-extension
```

## 🎯 Uso Rápido

### 1. Registrar o Service Provider

```php
// bootstrap/app.php
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;

$app->register(new CycleServiceProvider($app));
```

### 2. Configurar Variáveis de Ambiente

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Usar no Controller

```php
use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;

class UserController
{
    public function index(CycleRequest $request): JsonResponse
    {
        // Buscar usuários com paginação automática
        $users = $request->paginate(
            $request->repository(User::class)->select(),
            page: 1,
            perPage: 10
        );
        
        return response()->json($users);
    }
    
    public function store(CycleRequest $request): JsonResponse
    {
        // Criar entidade a partir dos dados da request
        $user = $request->entity(User::class, [
            'name' => $request->input('name'),
            'email' => $request->input('email')
        ]);
        
        $request->em->persist($user);
        $request->em->run();
        
        return response()->json($user);
    }
}
```

## 🧪 Executar Testes

```bash
# Todos os testes (exceto integração complexa)
vendor/bin/phpunit

# Apenas testes unitários
vendor/bin/phpunit tests/Unit/

# Incluir testes de integração
vendor/bin/phpunit --group integration
```

## 📈 Qualidade do Código

- **PHPStan Nível 9**: Zero erros de tipagem
- **PSR-12**: Padrões de código seguidos
- **100% Testado**: Cobertura completa das funcionalidades principais
- **Type Safety**: Interfaces bem definidas

## 🔧 Funcionalidades Avançadas

### Repository Factory com Cache
```php
$factory = $app->get('cycle.repository');
$userRepo = $factory->getRepository(User::class); // Cached automatically
```

### Middleware de Transação
```php
$app->use(new TransactionMiddleware($app));
```

### Sistema de Monitoramento
```php
use CAFernandes\ExpressPHP\CycleORM\Monitoring\MetricsCollector;

// Métricas automáticas de queries, cache, etc.
$metrics = MetricsCollector::getMetrics();
```

## 📚 Documentação Completa

- [Documentação Principal](docs/index.md)
- [Guia de Contribuição](CONTRIBUTING.md)
- [Arquitetura Técnica](docs/techinical/)
- [Exemplos de Implementação](docs/implementions/)

## 🤝 Contribuição

Contribuições são bem-vindas! Consulte [CONTRIBUTING.md](CONTRIBUTING.md) para guidelines.

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.
