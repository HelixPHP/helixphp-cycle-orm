<?php

namespace PivotPHP\CycleORM\Helpers;

class CycleHelpers
{
    /**
     * Paginação otimizada com cache de count.
     *
     * @param object $query      Query do ORM (ex: Select)
     * @param int    $page       Página atual
     * @param int    $perPage    Itens por página
     * @param bool   $cacheCount Se deve usar cache para o count
     *
     * @return array{data: array<int, mixed>, pagination: array<string, bool|int>}
     */
    public static function paginate(object $query, int $page = 1, int $perPage = 15, bool $cacheCount = true): array
    {
        if ($page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0');
        }
        if ($perPage < 1 || $perPage > 1000) {
            throw new \InvalidArgumentException('Per page must be between 1 and 1000');
        }
        $offset = ($page - 1) * $perPage;
        $count = 0;
        if (is_object($query) && method_exists($query, 'count')) {
            $count = $query->count();
        }
        if (
            ($offset > 0 || $perPage < $count)
            && is_object($query)
            && method_exists($query, 'limit')
            && method_exists($query, 'offset')
        ) {
            $query = $query->limit($perPage)->offset($offset);
        }
        $items = is_object($query) && method_exists($query, 'fetchAll') ? $query->fetchAll() : [];
        $lastPage = max(1, (int) ceil($count / $perPage));

        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $count,
                'last_page' => $lastPage,
                'from' => $count > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $count),
                'has_more' => $page < $lastPage,
            ],
        ];
    }

    /**
     * Filtros com sanitização e validação.
     *
     * @param object               $query         Query do ORM
     * @param array<string, mixed> $filters       Filtros a aplicar
     * @param array<int, string>   $allowedFields Campos permitidos
     *
     * @return object Query modificada
     */
    public static function applyFilters(object $query, array $filters, array $allowedFields = []): object
    {
        foreach ($filters as $field => $value) {
            if (!empty($allowedFields) && !in_array($field, $allowedFields)) {
                continue;
            }
            if (null === $value || '' === $value || [] === $value) {
                continue;
            }
            if (is_object($query) && method_exists($query, 'where')) {
                if (is_array($value)) {
                    $query = $query->where($field, 'IN', array_filter($value));
                } elseif (is_string($value) && false !== strpos($value, '%')) {
                    $query = $query->where($field, 'LIKE', $value);
                } elseif (
                    is_string($value)
                    && preg_match(
                        '/^(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})$/',
                        $value,
                        $matches
                    )
                ) {
                    $query = $query->where($field, '>=', $matches[1])
                        ->where($field, '<=', $matches[2]);
                } else {
                    $query = $query->where($field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Ordenação dinâmica.
     *
     * @param object             $query         Query do ORM
     * @param null|string        $sortBy        Campo para ordenar
     * @param string             $direction     Direção (asc|desc)
     * @param array<int, string> $allowedFields Campos permitidos
     *
     * @return object Query modificada
     */
    public static function applySorting(
        object $query,
        ?string $sortBy = null,
        string $direction = 'asc',
        array $allowedFields = []
    ): object {
        if (!$sortBy) {
            return $query;
        }
        if (!empty($allowedFields) && !in_array($sortBy, $allowedFields)) {
            throw new \InvalidArgumentException("Sort field '{$sortBy}' is not allowed");
        }
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException("Sort direction must be 'asc' or 'desc'");
        }
        if (is_object($query) && method_exists($query, 'orderBy')) {
            return $query->orderBy($sortBy, $direction);
        }

        return $query;
    }

    /**
     * Busca textual simples.
     *
     * @param object             $query        Query do ORM
     * @param null|string        $search       Termo de busca
     * @param array<int, string> $searchFields Campos pesquisáveis
     *
     * @return object Query modificada
     */
    public static function applySearch(object $query, ?string $search = null, array $searchFields = []): object
    {
        if (!$search || empty($searchFields)) {
            return $query;
        }
        $search = '%' . trim($search) . '%';
        if (is_object($query) && method_exists($query, 'where')) {
            return $query->where(
                function ($subQuery) use ($search, $searchFields) {
                    foreach ($searchFields as $field) {
                        if (is_object($subQuery) && method_exists($subQuery, 'orWhere')) {
                            $subQuery->orWhere($field, 'LIKE', $search);
                        }
                    }
                }
            );
        }

        return $query;
    }
}
