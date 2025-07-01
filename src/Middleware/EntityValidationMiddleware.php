<?php

namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;
use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;

class EntityValidationMiddleware
{
  /**
   * Middleware de validação de entidade
   *
   * @param Request $req
   * @param Response $res
   * @param callable(Request, Response): void $next
   * @return void
   */
  public function handle(Request $req, Response $res, callable $next): void
  {
    // Garante que o request é um CycleRequest
    $cycleReq = $req instanceof CycleRequest ? $req : new CycleRequest($req);
    // validateEntity já está disponível no wrapper
    $next($cycleReq, $res);
  }
  /**
   * Validação de entidade
   * @param object $entity Entidade a ser validada
   * @return array{valid: bool, errors: array<int, string>}
   */
  public function validateEntity(object $entity): array
  {
    $errors = [];

    try {
      // Validação básica usando Reflection
      $reflection = new \ReflectionClass($entity);

      foreach ($reflection->getProperties() as $property) {
        $property->setAccessible(true);
        $value = $property->getValue($entity);

        // Verificar required fields (convenção: não nullable)
        $type = $property->getType();
        if ($type && !$type->allowsNull() && $value === null) {
          $errors[] = "Field {$property->getName()} is required";
        }

        // Validação de tipos básicos
        if ($value !== null && $type) {
          $typeName = $type->getName();
          if ($typeName === 'string' && !is_string($value)) {
            $errors[] = "Field {$property->getName()} must be a string";
          } elseif ($typeName === 'int' && !is_int($value)) {
            $errors[] = "Field {$property->getName()} must be an integer";
          }
        }
      }
    } catch (\Exception $e) {
      $errors[] = "Validation error: " . $e->getMessage();
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors
    ];
  }
}
