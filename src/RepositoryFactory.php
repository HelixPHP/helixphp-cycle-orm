<?php
namespace ExpressPHP\CycleORM;

use Cycle\ORM\ORM;
use Cycle\ORM\RepositoryInterface;

/**
 * Factory para criação de repositories
 */
class RepositoryFactory
{
    private ORM $orm;
    private array $repositories = [];

    public function __construct(ORM $orm)
    {
        $this->orm = $orm;
    }

    /**
     * Obtém repository para uma entidade
     */
    public function getRepository(string $entityClass): RepositoryInterface
    {
        if (!isset($this->repositories[$entityClass])) {
            $this->repositories[$entityClass] = $this->orm->getRepository($entityClass);
        }

        return $this->repositories[$entityClass];
    }

    /**
     * Cria repository customizado
     */
    public function createRepository(string $repositoryClass, string $entityClass): RepositoryInterface
    {
        return new $repositoryClass(
            $this->orm->getSelect($entityClass),
            $this->orm
        );
    }
}