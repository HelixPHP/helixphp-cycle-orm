<?php

namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;
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

        // Cria o CycleRequest wrapper
        $cycleRequest = new CycleRequest($req);

        // Obtém os serviços do Cycle ORM do container
        $orm = $container->get('cycle.orm');
        $em = $container->get('cycle.em');
        $db = $container->get('cycle.database');
        $repository = $container->get('cycle.repository');

        // Injeta os serviços diretamente no CycleRequest
        if ($orm instanceof ORMInterface) {
            $cycleRequest->orm = $orm;
        }

        if ($em instanceof EntityManagerInterface) {
            $cycleRequest->em = $em;
        }

        if ($db instanceof DatabaseInterface) {
            $cycleRequest->db = $db;
        }

        // Passa o CycleRequest wrapper para o próximo handler
        $next($cycleRequest, $res);
    }

}
