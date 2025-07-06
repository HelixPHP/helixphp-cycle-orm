<?php

namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;
use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;

class TransactionMiddleware
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Compatível com padrão callable do Express-PHP.
     *
     * @param callable($req, Response):void $next função next do Express-PHP, recebe Request e Response
     */
    public function __invoke($req, Response $res, callable $next): void
    {
        $this->handle($req, $res, $next);
    }

    /**
     * Middleware de transação para Cycle ORM.
     *
     * @param callable(Request, Response):void $next função next do Express-PHP, recebe Request e Response
     */
    public function handle(Request|CycleRequest $req, Response $res, callable $next): void
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

            // If we have a CycleRequest, pass the original request to the next middleware
            // to ensure compatibility with the route handler
            if ($req instanceof CycleRequest) {
                $next($req->getOriginalRequest(), $res);
            } else {
                $next($req, $res);
            }

            // Commit apenas se há mudanças
            if (is_object($em) && method_exists($em, 'commitTransaction')) {
                $em->commitTransaction();
            } elseif (is_object($em) && method_exists($em, 'run')) {
                $em->run();
            }
            $this->logDebug('Transaction committed');
        } catch (\Exception $e) {
            try {
                if (is_object($em) && method_exists($em, 'rollbackTransaction')) {
                    $em->rollbackTransaction();
                } elseif (is_object($em) && method_exists($em, 'clean')) {
                    $em->clean();
                }
                $this->logDebug('Transaction rolled back due to error: ' . $e->getMessage());
            } catch (\Exception $rollbackException) {
                $this->logError('Rollback failed: ' . $rollbackException->getMessage());
            }
            throw $e;
        }
    }

    private function getRouteInfo(Request|CycleRequest $req): string
    {
        $method = property_exists($req, 'method')
            && (is_string($req->method) || is_numeric($req->method))
            ? (string) $req->method
            : 'Unknown';

        $uri = property_exists($req, 'pathCallable')
            && (is_string($req->pathCallable) || is_numeric($req->pathCallable))
            ? (string) $req->pathCallable
            : (property_exists($req, 'path')
                && (is_string($req->path) || is_numeric($req->path))
                ? (string) $req->path
                : 'Unknown');

        return "{$method} {$uri}";
    }

    private function logDebug(string $message): void
    {
        // Fallback para verificar debug sem usar config helper
        $debug = $_ENV['APP_DEBUG'] ?? $_ENV['app_debug'] ?? false;
        if ($debug) {
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
