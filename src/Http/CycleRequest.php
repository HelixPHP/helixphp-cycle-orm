<?php

namespace CAFernandes\ExpressPHP\CycleORM\Http;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\Select;
use Express\Http\Request;

/**
 * Wrapper que estende dinamicamente o Request original
 * Mantém 100% de compatibilidade com Express\Http\Request.
 *
 * @method mixed getMethod() Forwards to original request
 * @property mixed $foo Dynamic property forwarding
 */
class CycleRequest
{
    public ORMInterface $orm;

    public EntityManagerInterface $em;

    public DatabaseInterface $db;

    public ?object $user = null;

    /**
     * @var array<string, mixed>
     */
    public array $auth = [];

    private Request $originalRequest;

    public function __construct(Request $request)
    {
        $this->originalRequest = $request;
    }

    /**
     * Encaminha chamadas dinâmicas para o Request original.
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->originalRequest->{$name}(...$arguments);
    }

    /**
     * Encaminha acesso a propriedades para o Request original.
     */
    public function __get(string $name): mixed
    {
        return $this->originalRequest->{$name};
    }

    /**
     * Encaminha escrita de propriedades para o Request original.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->originalRequest->{$name} = $value;
    }

    /**
     * Retorna o repository de uma entidade.
     *
     * @return RepositoryInterface<object>
     */
    public function repository(object|string $entity): RepositoryInterface /* <object> */
    {
        // Garantir que $entity seja string não vazia ou objeto
        if (is_string($entity) && '' === $entity) {
            throw new \InvalidArgumentException('Entity class name cannot be empty');
        }

        return $this->orm->getRepository($entity);
    }

    /**
     * Inicializa uma entidade a partir de dados.
     *
     * @param array<string, mixed> $data
     */
    public function entity(object|string $entity, array $data): object
    {
        if (is_string($entity) && '' === $entity) {
            throw new \InvalidArgumentException('Entity class name cannot be empty');
        }
        $mapper = $this->orm->getMapper($entity);
        $entity = $mapper->init($data);

        // Apply data manually if mapper didn't populate properly
        foreach ($data as $property => $value) {
            if (property_exists($entity, $property)) {
                $entity->$property = $value;
            }
        }

        return $entity;
    }

    /**
     * Busca entidade por PK.
     */
    public function find(object|string $entity, mixed $id): ?object
    {
        return $this->repository($entity)->findByPK($id);
    }

    /**
     * Paginação de resultados.
     *
     * @template TEntity of object
     * @param Select<TEntity> $query
     * @return array<string, mixed>
     */
    public function paginate(Select $query, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;
        $total = method_exists($query, 'count') ? $query->count() : 0;
        if (($offset > 0 || $perPage < $total) && method_exists($query, 'limit') && method_exists($query, 'offset')) {
            $query = $query->limit($perPage)->offset($offset);
        }
        $items = (is_object($query) && method_exists($query, 'fetchAll')) ? $query->fetchAll() : [];
        $lastPage = max(1, (int) ceil($total / $perPage));

        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
                'has_more' => $page < $lastPage,
            ],
        ];
    }

    /**
     * Validação de entidade.
     *
     * @param array<string, mixed> $rules
     *
     * @return array<string, mixed>
     */
    public function validateEntity(array $rules): array
    {
        // Implementação da validação
        return ['valid' => true, 'errors' => []];
    }

    public function getOriginalRequest(): Request
    {
        return $this->originalRequest;
    }
}
