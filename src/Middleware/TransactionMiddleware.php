<?php
namespace CAFernandes\ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;
use Express\Core\Application;
use Cycle\ORM\EntityManager;

/**
 * CORREÇÃO: Transaction middleware com melhor controle de erro
 */
class TransactionMiddleware
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        /** @var EntityManager $em */
        $em = $this->app->make('cycle.em');
        $transactionStarted = false;

        try {
            // CORREÇÃO: Marcar início de transação lógica
            $this->app->fireAction('cycle.transaction.starting', ['request' => $req]);
            $transactionStarted = true;

            $next();

            // CORREÇÃO: Commit apenas se há mudanças pendentes
            if ($em->hasChanges()) {
                $changes = $this->getEntityChanges($em);

                $em->run();

                // CORREÇÃO: Dispatch eventos detalhados
                $this->app->fireAction('cycle.transaction.committed', [
                    'request' => $req,
                    'changes' => $changes
                ]);

                $this->app->logger()->debug('Transaction committed', [
                    'entities_count' => count($changes),
                    'route' => $req->getUri()
                ]);
            }

        } catch (\Exception $e) {
            if ($transactionStarted) {
                // CORREÇÃO: Rollback mais robusto
                try {
                    $em->clean();

                    $this->app->fireAction('cycle.transaction.rolled_back', [
                        'request' => $req,
                        'exception' => $e
                    ]);

                    $this->app->logger()->warning('Transaction rolled back', [
                        'error' => $e->getMessage(),
                        'route' => $req->getUri()
                    ]);

                } catch (\Exception $rollbackException) {
                    $this->app->logger()->error('Rollback failed', [
                        'original_error' => $e->getMessage(),
                        'rollback_error' => $rollbackException->getMessage()
                    ]);
                }
            }

            throw $e;
        }
    }

    /**
     * NOVO: Obter informações sobre mudanças de entidades
     */
    private function getEntityChanges(EntityManager $em): array
    {
        // CORREÇÃO: Usar reflection para acessar mudanças internas
        $reflection = new \ReflectionObject($em);

        try {
            $heapProperty = $reflection->getProperty('heap');
            $heapProperty->setAccessible(true);
            $heap = $heapProperty->getValue($em);

            // Simplificado - em produção seria mais detalhado
            return ['changes_detected' => $em->hasChanges()];
        } catch (\Exception $e) {
            return ['changes_detected' => true];
        }
    }
}