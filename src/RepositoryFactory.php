<?php

namespace CAFernandes\ExpressPHP\CycleORM;

use Cycle\ORM\ORM;
use Cycle\ORM\RepositoryInterface;

/**
 * CORREÇÃO: Repository Factory com cache e validação
 */
class RepositoryFactory
{
  private ORM $orm;
  private array $repositories = [];
  private array $customRepositories = [];

  public function __construct(ORM $orm)
  {
    $this->orm = $orm;
  }

  /**
   * CORREÇÃO: Repository com cache e validação
   */
  public function getRepository(string $entityClass): RepositoryInterface
  {
    // CORREÇÃO: Validar classe de entidade
    if (!class_exists($entityClass)) {
      throw new \InvalidArgumentException("Entity class {$entityClass} does not exist");
    }

    // CORREÇÃO: Verificar se entidade está registrada no schema
    $role = $this->orm->resolveRole($entityClass);
    if (!$role) {
      throw new \InvalidArgumentException("Entity {$entityClass} is not registered in Cycle schema");
    }

    // CORREÇÃO: Cache de repositories
    if (!isset($this->repositories[$entityClass])) {
      $this->repositories[$entityClass] = $this->orm->getRepository($entityClass);
    }

    return $this->repositories[$entityClass];
  }

  /**
   * CORREÇÃO: Registro de repository customizado
   */
  public function registerCustomRepository(string $entityClass, string $repositoryClass): void
  {
    if (!class_exists($repositoryClass)) {
      throw new \InvalidArgumentException("Repository class {$repositoryClass} does not exist");
    }

    if (!is_subclass_of($repositoryClass, RepositoryInterface::class)) {
      throw new \InvalidArgumentException("Repository class {$repositoryClass} must implement RepositoryInterface");
    }

    $this->customRepositories[$entityClass] = $repositoryClass;
  }

  /**
   * NOVO: Limpar cache de repositories
   */
  public function clearCache(): void
  {
    $this->repositories = [];
  }

  /**
   * NOVO: Estatísticas de uso
   */
  public function getStats(): array
  {
    return [
      'cached_repositories' => count($this->repositories),
      'custom_repositories' => count($this->customRepositories),
      'entities' => array_keys($this->repositories)
    ];
  }
}
