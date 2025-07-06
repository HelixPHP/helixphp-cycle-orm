<?php

namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;
use CAFernandes\ExpressPHP\CycleORM\RepositoryFactory;
use Cycle\Database\DatabaseInterface;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\ORMInterface;
use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;

/**
 * Middleware compatível com arquitetura real do Express-PHP.
 */
class CycleMiddleware
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Tornar o middleware compatível com o padrão callable do Express-PHP.
     *
     * @param callable(Request, Response):void $next função next do Express-PHP
     */
    public function __invoke(Request $req, Response $res, callable $next): void
    {
        $this->handle($req, $res, $next);
    }

    /**
     * Middleware principal do Cycle ORM.
     *
     * @param callable(Request, Response):void $next função next do Express-PHP
     */
    public function handle(Request $req, Response $res, callable $next): void
    {
        $container = $this->app->getContainer();
        if (!$container->has('cycle.orm')) {
            throw new \RuntimeException('Cycle ORM not properly registered');
        }

        // Adiciona os serviços do Cycle ORM como atributos dinâmicos no request
        $orm = $container->get('cycle.orm');
        $em = $container->get('cycle.em');
        $db = $container->get('cycle.database');
        $repository = $container->get('cycle.repository');

        if ($orm instanceof ORMInterface) {
            $req->setAttribute('orm', $orm);
            $req->setAttribute('cycle.orm', $orm); // Alias para compatibilidade
        }

        if ($em instanceof EntityManagerInterface) {
            $req->setAttribute('em', $em);
            $req->setAttribute('cycle.em', $em); // Alias para compatibilidade
        }

        if ($db instanceof DatabaseInterface) {
            $req->setAttribute('db', $db);
            $req->setAttribute('cycle.database', $db); // Alias para compatibilidade
        }

        // Adiciona o repository factory
        $req->setAttribute('repository', $repository);
        $req->setAttribute('cycle.repository', $repository); // Alias para compatibilidade

        // Adiciona métodos helper do Cycle ORM
        if (
            $orm instanceof ORMInterface
            && $em instanceof EntityManagerInterface
            && $repository instanceof RepositoryFactory
        ) {
            $this->addCycleHelpers($req, $orm, $em, $repository);
        }

        $next($req, $res);
    }

    /**
     * Adiciona métodos helper do Cycle ORM como closures no request.
     */
    private function addCycleHelpers(
        Request $req,
        ORMInterface $orm,
        EntityManagerInterface $em,
        RepositoryFactory $repository
    ): void {
        // Helper para obter repository de uma entidade
        $req->setAttribute(
            'getRepository',
            function (string $entityClass) use ($repository) {
            /** @var class-string $entityClass */
                return $repository->getRepository($entityClass);
            }
        );

        // Helper para criar entidade a partir de dados
        $req->setAttribute(
            'createEntity',
            function (string $entityClass, array $data) {
                $entity = new $entityClass();
                foreach ($data as $key => $value) {
                    if (property_exists($entity, $key)) {
                        $entity->$key = $value;
                    }
                }
                return $entity;
            }
        );

        // Helper para buscar entidade por ID
        $req->setAttribute(
            'findEntity',
            function (string $entityClass, $id) use ($repository) {
            /** @var class-string $entityClass */
                return $repository->getRepository($entityClass)->findByPK($id);
            }
        );

        // Helper para persistir entidade
        $req->setAttribute(
            'persistEntity',
            function ($entity) use ($em) {
                $em->persist($entity);
                return $entity;
            }
        );

        // Helper para remover entidade
        $req->setAttribute(
            'removeEntity',
            function ($entity) use ($em) {
                $em->delete($entity);
                return $entity;
            }
        );
    }
}
