<?php

namespace CAFernandes\ExpressPHP\CycleORM\Health;

use Express\Http\Request;
use Express\Http\Response;
use Express\Core\Application;

/**
 * Middleware para expor endpoint de health check
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
            // @phpstan-ignore-next-line
            $path = $req->getPathInfo();
        } elseif (property_exists($req, 'path')) {
            $path = $req->path;
        } elseif (property_exists($req, 'pathCallable')) {
            $path = $req->pathCallable;
        } else {
            $path = null;
        }

        // Verificar se é uma requisição de health check
        if ($path === '/health/cycle' || $path === '/health') {
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

        $statusCode = $health['cycle_orm'] === 'healthy' ? 200 : 503;

        $res->status($statusCode)
        ->header('Content-Type', 'application/json')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->json($health);
    }
}
