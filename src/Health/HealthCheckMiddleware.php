<?php

namespace CAFernandes\ExpressPHP\CycleORM\Health;

use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;

/**
 * Middleware para expor endpoint de health check.
 */
class HealthCheckMiddleware
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        // Compatível com Express-PHP: prioriza getPathInfo(), depois path, depois pathCallable
        if (method_exists($req, 'getPathInfo') && is_callable([$req, 'getPathInfo'])) {
            $path = $req->getPathInfo();
        } elseif (property_exists($req, 'path')) {
            $path = $req->path;
        } elseif (property_exists($req, 'pathCallable')) {
            $path = $req->pathCallable;
        } else {
            $path = null;
        }

        // Verificar se é uma requisição de health check
        if ('/health/cycle' === $path || '/health' === $path) {
            $this->handleHealthCheck($req, $res);

            return;
        }

        $next();
    }

    private function handleHealthCheck(Request $req, Response $res): void
    {
        $detailed = (is_array($req->query) && isset($req->query['detailed'])) ? $req->query['detailed'] : false;

        if ($detailed) {
            $health = CycleHealthCheck::detailedCheck($this->app);
        } else {
            $health = CycleHealthCheck::check($this->app);
        }

        $statusCode = 'healthy' === $health['cycle_orm'] ? 200 : 503;

        $res->status($statusCode)
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->json($health)
        ;
    }
}
