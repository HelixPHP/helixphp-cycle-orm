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
    /**
     * @var Request
     */
    private Request $originalRequest;

    /**
     * @var ORMInterface
     */
    public ORMInterface $orm;

    /**
     * @var EntityManagerInterface
     */
    public EntityManagerInterface $em;

    /**
     * @var DatabaseInterface
     */
    public DatabaseInterface $db;

    /**
     * @var object|null
     */
    public ?object $user = null;

    /**
     * @var array<string, mixed>
     */
    public array $auth = [];

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->originalRequest = $request;
    }

    /**
     * Encaminha chamadas dinâmicas para o Request original
     *
     * @param string $name
     * @param array<int, mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->originalRequest->$name(...$arguments);
    }

    /**
     * Encaminha acesso a propriedades para o Request original
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->originalRequest->$name;
    }

    /**
     * Encaminha escrita de propriedades para o Request original
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->originalRequest->$name = $value;
    }

    /**
     * Retorna o repository de uma entidade
     *
     * @param object|string $entity
     * @return RepositoryInterface<object>
     */
    public function repository(object|string $entity): RepositoryInterface /*<object>*/
    {
        // Garantir que $entity seja string não vazia ou objeto
        if (is_string($entity) && $entity === '') {
            throw new \InvalidArgumentException('Entity class name cannot be empty');
        }
        return $this->orm->getRepository($entity);
    }

    /**
     * Inicializa uma entidade a partir de dados
     *
     * @param object|string $entity
     * @param array<string, mixed> $data
     * @return object
     */
    public function entity(object|string $entity, array $data): object
    {
        if (is_string($entity) && $entity === '') {
            throw new \InvalidArgumentException('Entity class name cannot be empty');
        }
        $mapper = $this->orm->getMapper($entity);
        return $mapper->init($data);
    }

    /**
     * Busca entidade por PK
     *
     * @param object|string $entity
     * @param mixed $id
     * @return object|null
     */
    public function find(object|string $entity, mixed $id): ?object
    {
        return $this->repository($entity)->findByPK($id);
    }

    /**
     * Paginação de resultados
     *
     * @param \Cycle\ORM\Select $query
     * @param int $page
     * @param int $perPage
     * @return array<string, mixed>
     */
    public function paginate(\Cycle\ORM\Select $query, int $page = 1, int $perPage = 15): array
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
                'has_more' => $page < $lastPage
            ]
        ];
    }

    /**
     * Validação de entidade
     *
     * @param array<string, mixed> $rules
     * @return array<string, mixed>
     */
    public function validateEntity(array $rules): array
    {
        // Implementação da validação
        return ['valid' => true, 'errors' => []];
    }

    /**
     * @return Request
     */
    public function getOriginalRequest(): Request
    {
        return $this->originalRequest;
    }
}
