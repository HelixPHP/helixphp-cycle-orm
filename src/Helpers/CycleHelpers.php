<?php
namespace ExpressPHP\CycleORM\Helpers;

/**
 * Helpers utilitários para Cycle ORM
 */
class CycleHelpers
{
    /**
     * Cria uma query paginada
     */
    public static function paginate($query, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;

        $total = $query->count();
        $items = $query->limit($perPage)->offset($offset)->fetchAll();

        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ];
    }

    /**
     * Aplica filtros dinâmicos
     */
    public static function applyFilters($query, array $filters): object
    {
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $query = $query->where($field, 'in', $value);
                } elseif (strpos($value, '%') !== false) {
                    $query = $query->where($field, 'like', $value);
                } else {
                    $query = $query->where($field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Aplica ordenação dinâmica
     */
    public static function applySorting($query, ?string $sortBy = null, string $direction = 'asc'): object
    {
        if ($sortBy) {
            $query = $query->orderBy($sortBy, $direction);
        }

        return $query;
    }
}
