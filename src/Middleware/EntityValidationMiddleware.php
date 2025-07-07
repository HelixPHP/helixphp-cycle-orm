<?php

namespace PivotPHP\CycleORM\Middleware;

use PivotPHP\CycleORM\Http\CycleRequest;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

class EntityValidationMiddleware
{
    /**
     * Middleware de validação de entidade.
     *
     * @param callable(CycleRequest|Request, Response):void $next função next do PivotPHP,
     *                                                            recebe Request ou CycleRequest e Response
     */
    public function handle(Request $req, Response $res, callable $next): void
    {
        // Sempre cria o wrapper CycleRequest
        $cycleReq = new CycleRequest($req);
        // validateEntity já está disponível no wrapper
        $next($cycleReq, $res);
    }

    /**
     * Validação de entidade.
     *
     * @param object $entity Entidade a ser validada
     *
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
                // Evita erro fatal em propriedades tipadas não inicializadas
                if (method_exists($property, 'isInitialized') && !$property->isInitialized($entity)) {
                    $errors[] = "Field {$property->getName()} is required (not initialized)";
                    continue;
                }
                $value = $property->getValue($entity);

                // Verificar required fields (convenção: não nullable)
                $type = $property->getType();
                if ($type && !$type->allowsNull() && null === $value) {
                    $errors[] = "Field {$property->getName()} is required";
                }

                // Validação de tipos básicos
                if (null !== $value && $type) {
                    $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type;
                    if ('string' === $typeName && !is_string($value)) {
                        $errors[] = "Field {$property->getName()} must be a string";
                    } elseif ('int' === $typeName && !is_int($value)) {
                        $errors[] = "Field {$property->getName()} must be an integer";
                    }
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Validation error: ' . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
