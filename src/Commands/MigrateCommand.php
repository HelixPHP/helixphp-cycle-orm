<?php

namespace CAFernandes\ExpressPHP\CycleORM\Commands;

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
   * Executa as migrações pendentes.
   *
   * @return int Código de status (0 = sucesso, 1 = erro)
   */
    private function migrate(): int
    {
        $this->info('Running migrations...');

        try {
            if (function_exists('app')) {
                $migrator = app('cycle.migrator');
            } else {
                $this->error('Application container not available');
                return 1;
            }

            $migrator->run();
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
            if (function_exists('app')) {
                $migrator = app('cycle.migrator');
            } else {
                $this->error('Application container not available');
                return 1;
            }

            $migrator->rollback();
            $this->info('Migration rolled back successfully.');

            return 0;
        } catch (\Exception $e) {
            $this->error('Rollback failed: ' . $e->getMessage());
            return 1;
        }
    }
}
