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
            // Verificar se serviços estão disponíveis
            if (!$this->app->getContainer()->has('cycle.orm')) {
                throw new \RuntimeException('Cycle ORM not properly registered');
            }

            // Injetar serviços principais
            $req->orm = $this->app->getContainer()->get('cycle.orm');
            $req->em = $this->app->getContainer()->get('cycle.em');
            $req->db = $this->app->getContainer()->get('cycle.database');

            // CORREÇÃO: Helper repository mais robusto
            $req->repository = function (string $entityClass) use ($req) {
                if (!class_exists($entityClass)) {
                    throw new \InvalidArgumentException("Entity class {$entityClass} does not exist");
                }
                return $req->orm->getRepository($entityClass);
            };

            // CORREÇÃO: Helper entity com validação aprimorada
            $req->entity = function (string $entityClass, array $data = []) {
                if (!class_exists($entityClass)) {
                    throw new \InvalidArgumentException("Entity class {$entityClass} does not exist");
                }

                $entity = new $entityClass();

                // Aplicar dados se fornecidos
                foreach ($data as $property => $value) {
                    if (property_exists($entity, $property)) {
                        $entity->$property = $value;
                    }
                }

                return $entity;
            };

            // CORREÇÃO: Helper find com validação
            $req->find = function (string $entityClass, $id) use ($req) {
                return $req->repository($entityClass)->findByPK($id);
            };

            // CORREÇÃO: Helper paginate
            $req->paginate = function ($query, int $page = 1, int $perPage = 15) {
                return \CAFernandes\ExpressPHP\CycleORM\Helpers\CycleHelpers::paginate($query, $page, $perPage);
            };

            // CORREÇÃO: Helper validateEntity
            $req->validateEntity = function ($entity) {
                $middleware = new \CAFernandes\ExpressPHP\CycleORM\Middleware\EntityValidationMiddleware($this->app);
                $reflection = new \ReflectionMethod($middleware, 'validateEntity');
                $reflection->setAccessible(true);
                return $reflection->invoke($middleware, $entity);
            };

            $next();

        } catch (\Exception $e) {
            // Log erro se logger disponível
            if (method_exists($this->app, 'logger')) {
                $this->app->logger()->error('Cycle middleware error: ' . $e->getMessage());
            }
            throw $e;
        }
    }
}
