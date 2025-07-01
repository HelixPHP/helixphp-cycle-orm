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

  /**
   * Middleware de transação para Cycle ORM
   *
   * @param Request $req
   * @param Response $res
   * @param callable(Request, Response):void $next Função next do Express-PHP, recebe Request e Response.
   * @return void
   */
    public function handle(Request $req, Response $res, callable $next): void
    {
      // Use sempre o container PSR-11 para buscar serviços
        if (method_exists($this->app, 'getContainer')) {
            $container = $this->app->getContainer();
            if ($container->has('cycle.em')) {
                $em = $container->get('cycle.em');
            } else {
                $next($req, $res);
                return;
            }
        } else {
          // fallback para make (testes antigos)
            $em = $this->app->make('cycle.em');
        }

        $transactionStarted = false;

        try {
          // Marcar início de transação
            $transactionStarted = true;
            $this->logDebug('Transaction started for route: ' . $this->getRouteInfo($req));

            $next($req, $res);

          // Commit apenas se há mudanças
            if (is_object($em) && method_exists($em, 'run')) {
                $em->run();
            }
            $this->logDebug('Transaction committed');
        } catch (\Exception $e) {
            try {
                if (is_object($em) && method_exists($em, 'clean')) {
                    $em->clean();
                }
                $this->logDebug('Transaction rolled back due to error: ' . $e->getMessage());
            } catch (\Exception $rollbackException) {
                $this->logError('Rollback failed: ' . $rollbackException->getMessage());
            }
            throw $e;
        }
    }

  /**
   * Compatível com padrão callable do Express-PHP
   *
   * @param Request $req
   * @param Response $res
   * @param callable(Request, Response):void $next Função next do Express-PHP, recebe Request e Response.
   * @return void
   */
    public function __invoke(Request $req, Response $res, callable $next): void
    {
        $this->handle($req, $res, $next);
    }

    private function getRouteInfo(Request $req): string
    {
        $method = property_exists($req, 'method') ? $req->method : 'Unknown';
        $uri = property_exists($req, 'pathCallable')
            ? $req->pathCallable
            : (property_exists($req, 'path') ? $req->path : 'Unknown');
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
            if (is_object($logger) && method_exists($logger, 'debug')) {
                $logger->debug($message, []);
            } elseif (is_object($logger) && method_exists($logger, 'error')) {
                $logger->error($message, []);
            }
        } else {
            error_log($message);
        }
    }
}
