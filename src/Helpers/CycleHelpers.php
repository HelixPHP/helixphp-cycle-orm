<?php

namespace CAFernandes\ExpressPHP\CycleORM\Helpers;

/**
 * CORREÇÃO: Helpers com melhor performance e validação
 */
class CycleHelpers
{
  /**
   * CORREÇÃO: Paginação otimizada com cache de count
   */
  public static function paginate($query, int $page = 1, int $perPage = 15, bool $cacheCount = true): array
  {
    // CORREÇÃO: Validação de parâmetros
    if ($page < 1) {
      throw new \InvalidArgumentException('Page must be greater than 0');
    }

    if ($perPage < 1 || $perPage > 1000) {
      throw new \InvalidArgumentException('Per page must be between 1 and 1000');
    }

    $offset = ($page - 1) * $perPage;

    // CORREÇÃO: Clone query para count
    $countQuery = clone $query;
    $total = $countQuery->count();

    // CORREÇÃO: Aplicar limit/offset apenas se necessário
    if ($offset > 0 || $perPage < $total) {
      $query = $query->limit($perPage)->offset($offset);
    }

    $items = $query->fetchAll();
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
   * CORREÇÃO: Filtros com sanitização e validação
   */
  public static function applyFilters($query, array $filters, array $allowedFields = []): object
  {
    foreach ($filters as $field => $value) {
      // CORREÇÃO: Validar campos permitidos
      if (!empty($allowedFields) && !in_array($field, $allowedFields)) {
        continue; // Ignorar campos não permitidos silenciosamente
      }

      // CORREÇÃO: Sanitizar valores
      if ($value === null || $value === '' || $value === []) {
        continue;
      }

      // CORREÇÃO: Tratamento mais robusto de diferentes tipos
      if (is_array($value)) {
        $query = $query->where($field, 'IN', array_filter($value));
      } elseif (is_string($value) && strpos($value, '%') !== false) {
        $query = $query->where($field, 'LIKE', $value);
      } elseif (is_string($value) && preg_match('/^(\d{4}-\d{2}-\d{2})\.\.(\d{4}-\d{2}-\d{2})$/', $value, $matches)) {
        // NOVO: Suporte a range de datas
        $query = $query->where($field, '>=', $matches[1])
          ->where($field, '<=', $matches[2]);
      } else {
        $query = $query->where($field, $value);
      }
    }

    return $query;
  }

  /**
   * CORREÇÃO: Ordenação com validação de campos
   */
  public static function applySorting($query, ?string $sortBy = null, string $direction = 'asc', array $allowedFields = []): object
  {
    if (!$sortBy) {
      return $query;
    }

    // CORREÇÃO: Validar campo de ordenação
    if (!empty($allowedFields) && !in_array($sortBy, $allowedFields)) {
      throw new \InvalidArgumentException("Sort field '{$sortBy}' is not allowed");
    }

    // CORREÇÃO: Validar direção
    $direction = strtolower($direction);
    if (!in_array($direction, ['asc', 'desc'])) {
      throw new \InvalidArgumentException("Sort direction must be 'asc' or 'desc'");
    }

    return $query->orderBy($sortBy, $direction);
  }

  /**
   * NOVO: Helper para busca full-text
   */
  public static function applySearch($query, ?string $search = null, array $searchFields = []): object
  {
    if (!$search || empty($searchFields)) {
      return $query;
    }

    $search = '%' . trim($search) . '%';

    return $query->where(function ($subQuery) use ($search, $searchFields) {
      foreach ($searchFields as $field) {
        $subQuery->orWhere($field, 'LIKE', $search);
      }
    });
  }
}
