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
 * CORREÇÃO: Middleware compatível com arquitetura real do Express-PHP
 *
 * @property mixed $orm
 * @property mixed $em
 * @property mixed $db
 * @property callable $repository
 * @property callable $entity
 * @property callable $find
 * @property callable $paginate
 * @property callable $validateEntity
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
      $cycleReq->orm = $container->get('cycle.orm');
      $cycleReq->em = $container->get('cycle.em');
      $cycleReq->db = $container->get('cycle.database');
      // Copiar propriedades customizadas
      if (isset($req->user)) {
        $cycleReq->user = $req->user;
      }
      if (isset($req->auth)) {
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
   * CORREÇÃO: Tornar o middleware compatível com o padrão callable do Express-PHP
   */
  public function __invoke(Request $req, Response $res, callable $next): void
  {
    $this->handle($req, $res, $next);
  }
}
