<?php
namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;
use Express\Core\Application;

/**
 * CORREÇÃO: Middleware compatível com arquitetura real do Express-PHP
 */
class CycleMiddleware
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * CORREÇÃO: Signature correta para middleware do Express-PHP
     */
    public function handle(Request $req, Response $res, callable $next): void
    {
        try {
            // CORREÇÃO: Verificar se serviços estão disponíveis
            if (!$this->app->has('cycle.orm')) {
                throw new \RuntimeException('Cycle ORM not properly registered');
            }

            // Injetar serviços com verificação de disponibilidade
            $req->orm = $this->app->make('cycle.orm');
            $req->em = $this->app->make('cycle.em');
            $req->db = $this->app->make('cycle.database');

            // CORREÇÃO: Helper mais robusto para repositories
            $req->repository = function (string $entityClass) use ($req) {
                if (!class_exists($entityClass)) {
                    throw new \InvalidArgumentException("Entity class {$entityClass} does not exist");
                }
                return $req->orm->getRepository($entityClass);
            };

            // CORREÇÃO: Helper para criação de entidades com validação
            $req->entity = function (string $entityClass, array $data = []) {
                if (!class_exists($entityClass)) {
                    throw new \InvalidArgumentException("Entity class {$entityClass} does not exist");
                }

                // CORREÇÃO: Usar reflection para criar entidade corretamente
                $reflection = new \ReflectionClass($entityClass);
                if ($reflection->getConstructor()) {
                    $constructor = $reflection->getConstructor();
                    $params = [];

                    foreach ($constructor->getParameters() as $param) {
                        $paramName = $param->getName();
                        if (isset($data[$paramName])) {
                            $params[] = $data[$paramName];
                        } elseif (!$param->isOptional()) {
                            throw new \InvalidArgumentException("Missing required parameter: {$paramName}");
                        }
                    }

                    return $reflection->newInstanceArgs($params);
                }

                return new $entityClass();
            };

            // CORREÇÃO: Dispatch event para hooks
            $this->app->fireAction('cycle.middleware.before', ['request' => $req]);

            $next();

            // CORREÇÃO: Dispatch event pós-processamento
            $this->app->fireAction('cycle.middleware.after', ['request' => $req, 'response' => $res]);

        } catch (\Exception $e) {
            // CORREÇÃO: Log de erro e propagação
            $this->app->logger()->error('Cycle middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
