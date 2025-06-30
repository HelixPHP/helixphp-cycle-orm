<?php

namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;
use Express\Core\Application;
use Cycle\ORM\EntityManager;

class TransactionMiddleware
{
  private Application $app;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function handle(Request $req, Response $res, callable $next): void
  {
    if (!$this->app->has('cycle.em')) {
      // Se não tem EM, apenas prosseguir
      $next();
      return;
    }

    /** @var EntityManager $em */
    $em = $this->app->make('cycle.em');
    $transactionStarted = false;

    try {
      // Marcar início de transação
      $transactionStarted = true;
      $this->logDebug('Transaction started for route: ' . $this->getRouteInfo($req));

      $next();

      // Commit apenas se há mudanças
      $em->run();
      $this->logDebug('Transaction committed');
    } catch (\Exception $e) {
      if ($transactionStarted) {
        try {
          $em->clean();
          $this->logDebug('Transaction rolled back due to error: ' . $e->getMessage());
        } catch (\Exception $rollbackException) {
          $this->logError('Rollback failed: ' . $rollbackException->getMessage());
        }
      }
      throw $e;
    }
  }

  public function __invoke(Request $req, Response $res, callable $next): void
  {
    $this->handle($req, $res, $next);
  }

  private function getRouteInfo(Request $req): string
  {
    $method = property_exists($req, 'method') ? $req->method : 'Unknown';
    $uri = property_exists($req, 'pathCallable') ? $req->pathCallable : (property_exists($req, 'path') ? $req->path : 'Unknown');
    return "{$method} {$uri}";
  }

  private function logDebug(string $message): void
  {
    if (config('app.debug', false)) {
      $this->logError($message); // Usar mesmo método para simplicidade
    }
  }

  private function logError(string $message): void
  {
    if ($this->app->getContainer()->has('logger')) {
      $logger = $this->app->getContainer()->get('logger');
      if (method_exists($logger, 'debug')) {
        $logger->debug($message, []);
      } elseif (method_exists($logger, 'error')) {
        $logger->error($message, []);
      }
    } else {
      error_log($message);
    }
  }
}
