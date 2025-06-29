<?php
namespace ExpressPHP\CycleORM\Commands;

use Express\Console\Command;

/**
 * Comando para executar migrações
 */
class MigrateCommand extends Command
{
    protected string $signature = 'cycle:migrate {--rollback : Rollback last migration}';
    protected string $description = 'Run database migrations';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        return $this->migrate();
    }

    /**
     * Executa migrações pendentes
     */
    private function migrate(): int
    {
        $this->info('Running migrations...');

        try {
            $migrator = app('cycle.migrator');
            $migrations = $migrator->run();

            if (empty($migrations)) {
                $this->info('No pending migrations.');
                return self::SUCCESS;
            }

            $this->info('Executed migrations:');
            foreach ($migrations as $migration) {
                $this->line('- ' . $migration->getState()->getName());
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Reverte última migração
     */
    private function rollback(): int
    {
        $this->info('Rolling back last migration...');

        try {
            $migrator = app('cycle.migrator');
            $migration = $migrator->rollback();

            if ($migration) {
                $this->info('Rolled back: ' . $migration->getState()->getName());
            } else {
                $this->info('No migrations to rollback.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Rollback failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
