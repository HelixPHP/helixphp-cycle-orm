<?php
namespace ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;

/**
 * Middleware para transações automáticas
 */
class TransactionMiddleware
{
    /**
     * Handle the middleware request
     */
    public function handle(Request $req, Response $res, callable $next): void
    {
        $em = app('cycle.em');

        try {
            $next();

            // Auto-commit se houver mudanças pendentes
            if ($em->hasChanges()) {
                $em->run();

                // Dispatch event
                app()->fireAction('cycle.transaction.committed', [
                    'entities' => $em->getChanges()
                ]);
            }
        } catch (\Exception $e) {
            // Auto-rollback limpando entity manager
            $em->clean();

            // Dispatch event
            app()->fireAction('cycle.transaction.rolled_back', [
                'exception' => $e
            ]);

            throw $e;
        }
    }
}
