<?php

namespace CAFernandes\ExpressPHP\CycleORM;

use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;

/**
 * Repository Factory com cache e validação.
 */
class RepositoryFactory
{
    private ORMInterface $orm;

    /**
     * @var array<string, RepositoryInterface<object>> Cache de repositories
     */
    private array $repositories = [];

    /**
     * @var array<string, class-string<RepositoryInterface<object>>> Custom repositories
     */
    private array $customRepositories = [];

    /**
     * @param ORMInterface $orm ORM do Cycle
     */
    public function __construct(ORMInterface $orm)
    {
        $this->orm = $orm;
    }

    /**
     * Obtém o repository de uma entidade, com cache.
     *
     * @param class-string|object $entityClass
     *
     * @return RepositoryInterface<object>
     */
    public function getRepository(object|string $entityClass): RepositoryInterface /* <object> */
    {
        $key = is_object($entityClass) ? get_class($entityClass) : $entityClass;
        if (!isset($this->repositories[$key])) {
            $this->repositories[$key] = $this->orm->getRepository($entityClass);
        }

        return $this->repositories[$key];
    }

    /**
     * Registra um repository customizado para uma entidade.
     *
     * @param class-string                              $entityClass
     * @param class-string<RepositoryInterface<object>> $repositoryClass
     */
    public function registerCustomRepository(string $entityClass, string $repositoryClass): void
    {
        if (!class_exists($repositoryClass)) {
            throw new \InvalidArgumentException("Repository class {$repositoryClass} does not exist");
        }

        if (!is_subclass_of($repositoryClass, RepositoryInterface::class)) {
            throw new \InvalidArgumentException(
                "Repository class {$repositoryClass} must implement RepositoryInterface"
            );
        }

        $this->customRepositories[$entityClass] = $repositoryClass;
    }

    /**
     * Limpa o cache de repositories.
     */
    public function clearCache(): void
    {
        $this->repositories = [];
    }

    /**
     * Retorna estatísticas de uso dos repositories.
     *
     * @return array<string, int|string[]>
     */
    public function getStats(): array
    {
        return [
            'cached_repositories' => count($this->repositories),
            'custom_repositories' => count($this->customRepositories),
            'entities' => array_keys($this->repositories),
        ];
    }
}
