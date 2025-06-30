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

    private function getRouteInfo(Request $req): string
    {
        $method = method_exists($req, 'getMethod') ? $req->getMethod() : 'Unknown';
        $uri = method_exists($req, 'getUri') ? $req->getUri() : 'Unknown';
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
        if (method_exists($this->app, 'logger') && $this->app->has('logger')) {
            $this->app->logger()->debug($message);
        } else {
            error_log($message);
        }
    }
}
