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
     * @param callable(Request, Response): void $next
     * @return void
     */
    public function handle(Request $req, Response $res, callable $next): void
    {
        try {
            // Se já é um CycleRequest, apenas segue
            if ($req instanceof CycleRequest) {
                $next($req, $res);
                return;
            }
            // Verificar se serviços estão disponíveis
            $container = $this->app->getContainer();
            if (!$container->has('cycle.orm')) {
                throw new \RuntimeException('Cycle ORM not properly registered');
            }
            // Criar wrapper
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
            // Copiar propriedades customizadas
            if (isset($req->user) && (is_object($req->user) || is_null($req->user))) {
                $cycleReq->user = $req->user;
            }
            if (isset($req->auth) && is_array($req->auth)) {
                $cycleReq->auth = $req->auth;
            }
            $next($cycleReq, $res);
        } catch (\Exception $e) {
            if ($this->app->getContainer()->has('logger')) {
                $logger = $this->app->getContainer()->get('logger');
                if (method_exists($logger, 'error')) {
                    $logger->error('Cycle middleware error: ' . $e->getMessage(), []);
                }
            }
            throw $e;
        }
    }

    /**
     * Tornar o middleware compatível com o padrão callable do Express-PHP
     *
     * @param Request $req
     * @param Response $res
     * @param callable(Request, Response): void $next
     * @return void
     */
    public function __invoke(Request $req, Response $res, callable $next): void
    {
        $this->handle($req, $res, $next);
    }
}
