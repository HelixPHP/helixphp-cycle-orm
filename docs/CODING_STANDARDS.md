# 📋 Padrões de Código - Express PHP Framework

## PSR-12 Extended Coding Style

Este documento define os padrões de código para o Express PHP Framework,
baseados em PSR-12 com extensões específicas do projeto.

### Formatação de Métodos

```php
// ✅ Métodos com poucos parâmetros
public function get(string $path, callable $handler): Route

// ✅ Métodos com muitos parâmetros
public function middleware(
    string $path,
    MiddlewareInterface $middleware,
    array $options = [],
    int $priority = 10
): self
```

### DocBlocks Padronizados

```php
/**
 * Descrição breve do método em uma linha.
 *
 * Descrição detalhada opcional que pode ser mais longa
 * e explicar comportamentos complexos do método.
 *
 * @param string $param Descrição do parâmetro
 * @param array $options Opções de configuração
 * @return ResponseInterface A resposta processada
 *
 * @throws InvalidArgumentException Se o parâmetro for inválido
 * @throws RuntimeException Se ocorrer erro de execução
 *
 * @since 2.1.0
 */
```

### Arrays e Estruturas de Dados

```php
// ✅ Arrays associativos
$config = [
    'key1' => 'value1',
    'key2' => [
        'nested' => 'value'
    ]
];

// ✅ Arrays simples multilinha
$items = [
    'item1',
    'item2',
    'item3'
];
```
