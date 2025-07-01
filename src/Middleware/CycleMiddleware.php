<?php

namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;
use Express\Core\Application;
use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\EntityManagerInterface;
use Cycle\Database\DatabaseInterface;

/**
 * Middleware compatível com arquitetura real do Express-PHP
 */
class CycleMiddleware
{
    /**
     * @var Application
     */
    private Application $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Middleware principal do Cycle ORM
     *
     * @param Request $req
     * @param Response $res
     * @param callable(Request|CycleRequest, Response):void $next Função next do Express-PHP, recebe Request ou CycleRequest e Response.
     * @return void
     */
    public function handle(Request $req, Response $res, callable $next): void
    {
        // Remove instanceof sempre falso
        // Sempre cria o wrapper CycleRequest
        $container = $this->app->getContainer();
        if (!$container->has('cycle.orm')) {
            throw new \RuntimeException('Cycle ORM not properly registered');
        }
        $cycleReq = new CycleRequest($req);
        $orm = $container->get('cycle.orm');
        $em = $container->get('cycle.em');
        $db = $container->get('cycle.database');
        if ($orm instanceof ORMInterface) {
            $cycleReq->orm = $orm;
        }
        if ($em instanceof EntityManagerInterface) {
            $cycleReq->em = $em;
        }
        if ($db instanceof DatabaseInterface) {
            $cycleReq->db = $db;
        }
        if (isset($req->user) && (is_object($req->user) || is_null($req->user))) {
            $cycleReq->user = $req->user;
        }
        if (isset($req->auth) && is_array($req->auth)) {
            $cycleReq->auth = $req->auth;
        }
        $next($cycleReq, $res);
    }

    /**
     * Tornar o middleware compatível com o padrão callable do Express-PHP
     *
     * @param Request $req
     * @param Response $res
     * @param callable(Request|CycleRequest, Response):void $next Função next do Express-PHP, recebe Request ou CycleRequest e Response.
     * @return void
     */
    public function __invoke(Request $req, Response $res, callable $next): void
    {
        $this->handle($req, $res, $next);
    }
}
