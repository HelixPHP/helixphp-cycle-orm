<?php

namespace PivotPHP\CycleORM\Health;

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

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
        // Use the public method getPathCallable() to get the actual request path
        $path = $req->getPathCallable();

        // Verificar se é uma requisição de health check
        if ('/health/cycle' === $path || '/health' === $path) {
            $this->handleHealthCheck($req, $res);

            return;
        }

        $next();
    }

    private function handleHealthCheck(Request $req, Response $res): void
    {
        $detailed = $req->get('detailed', false);

        if ($detailed) {
            $health = CycleHealthCheck::detailedCheck($this->app);
        } else {
            $health = CycleHealthCheck::check($this->app);
        }

        $statusCode = 'healthy' === $health['cycle_orm'] ? 200 : 503;

        $res->status($statusCode)
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->json($health);
    }
}
