<?php
namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;
use Express\Core\Application;

class EntityValidationMiddleware
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        $validateEntity = function (object $entity) {
            return $this->validateEntity($entity);
        };

        if (method_exists($req, 'setAttribute')) {
            $req->setAttribute('validateEntity', $validateEntity);
        } else {
            $req->validateEntity = $validateEntity;
        }

        $next();
    }

    private function validateEntity(object $entity): array
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