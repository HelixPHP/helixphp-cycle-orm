# Guia Técnico: RepositoryFactory

O `RepositoryFactory` é responsável por fornecer instâncias de repositórios para entidades do Cycle ORM, com suporte a cache e repositórios customizados.

## Propriedades
- **ORM $orm**: Instância do ORM do Cycle.
- **array $repositories**: Cache de repositórios instanciados.
- **array $customRepositories**: Mapeamento de repositórios customizados por entidade.

## Métodos Públicos
- `__construct(ORM $orm)`: Inicializa o factory com a instância do ORM.
- `getRepository(object|string $entityClass): RepositoryInterface`: Retorna o repositório da entidade, utilizando cache.
- `registerCustomRepository(string $entityClass, string $repositoryClass): void`: Registra um repositório customizado para uma entidade.
- `clearCache(): void`: Limpa o cache de repositórios.
- `getStats(): array`: Retorna estatísticas de uso dos repositórios.

## Boas Práticas
- Utilize `registerCustomRepository` para customizar o comportamento de persistência de entidades específicas.
- Utilize `clearCache` em cenários de atualização dinâmica de entidades.
- Consulte `getStats` para monitorar o uso e performance dos repositórios.

## Exemplo de Uso
```php
$factory = new RepositoryFactory($orm);
$factory->registerCustomRepository(User::class, UserRepository::class);
$userRepo = $factory->getRepository(User::class);
```

Consulte a documentação de entidades e repositórios customizados para exemplos avançados.
