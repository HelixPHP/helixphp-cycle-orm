<?php

namespace PivotPHP\CycleORM\Commands;

/**
 * Comando para executar e reverter migrações do Cycle ORM.
 *
 * Exemplos de uso:
 *   php bin/console cycle:migrate
 *   php bin/console cycle:migrate --rollback
 *
 * Métodos:
 *   - handle(): Executa o comando principal.
 *   - migrate(): Executa as migrações pendentes.
 *   - rollback(): Reverte a última migração.
 */
class MigrateCommand extends BaseCommand
{
    /**
     * Instância da aplicação ou container.
     *
     * @var null|object
     */
    protected $app;

    /**
     * Executa o comando principal para migrações.
     *
     * @return int Código de status (0 = sucesso, 1 = erro)
     */
    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        return $this->migrate();
    }

    /**
     * Resolve um serviço do container PSR-11 ou via app().
     */
    protected function getService(string $id): object
    {
        // PSR-11: se existir $this->app e for container
        if (property_exists($this, 'app') && is_object($this->app)) {
            $container = $this->app;
            if (method_exists($container, 'has') && $container->has($id)) {
                return method_exists($container, 'get') ? $container->get($id) : null;
            }
        }
        // Fallback para helper global app()
        if (function_exists('app')) {
            return app($id);
        }
        throw new \RuntimeException("Service '{$id}' not found in container or app().");
    }

    /**
     * Executa as migrações pendentes.
     *
     * @return int Código de status (0 = sucesso, 1 = erro)
     */
    private function migrate(): int
    {
        $this->info('Running migrations...');

        try {
            $migrator = $this->getService('cycle.migrator');
            if (is_object($migrator) && method_exists($migrator, 'run')) {
                $migrator->run();
            } else {
                throw new \RuntimeException('Migrator service is invalid.');
            }
            $this->info('Migrations executed successfully.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Reverte a última migração executada.
     *
     * @return int Código de status (0 = sucesso, 1 = erro)
     */
    private function rollback(): int
    {
        $this->info('Rolling back last migration...');

        try {
            $migrator = $this->getService('cycle.migrator');
            if (is_object($migrator) && method_exists($migrator, 'run')) {
                $migrator->run();
            } else {
                throw new \RuntimeException('Migrator service is invalid.');
            }
            $this->info('Migration rolled back successfully.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Rollback failed: ' . $e->getMessage());

            return 1;
        }
    }
}
