<?php

namespace CAFernandes\ExpressPHP\CycleORM\Commands;

/**
 * Comando para migrações - Versão corrigida
 */
class MigrateCommand extends BaseCommand
{
  public function handle(): int
  {
    if ($this->option('rollback')) {
      return $this->rollback();
    }

    return $this->migrate();
  }

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

      $migrations = $migrator->run();

      if (empty($migrations)) {
        $this->info('No pending migrations.');
        return 0;
      }

      $this->info('Executed migrations:');
      foreach ($migrations as $migration) {
        $this->line('- ' . $migration->getState()->getName());
      }

      return 0;
    } catch (\Exception $e) {
      $this->error('Migration failed: ' . $e->getMessage());
      return 1;
    }
  }

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

      $migration = $migrator->rollback();

      if ($migration) {
        $this->info('Rolled back: ' . $migration->getState()->getName());
      } else {
        $this->info('No migrations to rollback.');
      }

      return 0;
    } catch (\Exception $e) {
      $this->error('Rollback failed: ' . $e->getMessage());
      return 1;
    }
  }
}
