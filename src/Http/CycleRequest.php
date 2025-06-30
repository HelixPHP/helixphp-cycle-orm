<?php

namespace CAFernandes\ExpressPHP\CycleORM\Http;

use Express\Http\Request;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\RepositoryInterface;
use Cycle\Database\DatabaseInterface;

/**
 * Wrapper que estende dinamicamente o Request original
 * Mantém 100% de compatibilidade com Express\Http\Request
 */
class CycleRequest
{
  private Request $originalRequest;
  public ORMInterface $orm;
  public EntityManagerInterface $em;
  public DatabaseInterface $db;
  public mixed $user = null;
  public array $auth = [];

  public function __construct(Request $request)
  {
    $this->originalRequest = $request;
  }

  public function __call(string $name, array $arguments)
  {
    return $this->originalRequest->$name(...$arguments);
  }

  public function __get(string $name)
  {
    return $this->originalRequest->$name;
  }

  public function __set(string $name, $value): void
  {
    $this->originalRequest->$name = $value;
  }

  public function repository(string $entity): RepositoryInterface
  {
    return $this->orm->getRepository($entity);
  }

  public function entity(string $entity, array $data): object
  {
    $mapper = $this->orm->getMapper($entity);
    return $mapper->init($data);
  }

  public function find(string $entity, mixed $id): ?object
  {
    return $this->repository($entity)->findByPK($id);
  }

  public function paginate(\Cycle\ORM\Select $query, int $page = 1, int $perPage = 15): array
  {
    $total = clone $query;
    $total = $total->count();
    $items = $query
      ->limit($perPage)
      ->offset(($page - 1) * $perPage)
      ->fetchAll();
    return [
      'data' => $items,
      'total' => $total,
      'page' => $page,
      'per_page' => $perPage,
      'last_page' => (int) ceil($total / $perPage)
    ];
  }

  public function validateEntity(object $entity, array $rules = []): array
  {
    // Implementação da validação
    return ['valid' => true, 'errors' => []];
  }

  public function getOriginalRequest(): Request
  {
    return $this->originalRequest;
  }
}
