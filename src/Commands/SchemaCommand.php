<?php
namespace ExpressPHP\CycleORM\Commands;

use Express\Console\Command;

/**
 * Comando para sincronizar schema do banco
 */
class SchemaCommand extends Command
{
    protected string $signature = 'cycle:schema {--sync : Sync schema to database}';
    protected string $description = 'Manage Cycle ORM schema';

    public function handle(): int
    {
        if ($this->option('sync')) {
            return $this->syncSchema();
        }

        return $this->showSchema();
    }

    /**
     * Sincroniza schema com o banco
     */
    private function syncSchema(): int
    {
        $this->info('Synchronizing database schema...');

        try {
            $migrator = app('cycle.migrator');
            $migration = $migrator->run();

            if ($migration) {
                $this->info('Schema synchronized successfully!');
                $this->table(['Migration', 'Status'], [
                    [$migration->getState()->getName(), 'Executed']
                ]);
            } else {
                $this->info('Schema is already up to date.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to sync schema: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Mostra informações do schema
     */
    private function showSchema(): int
    {
        $this->info('Cycle ORM Schema Information');

        $orm = app('cycle.orm');
        $schema = $orm->getSchema();

        $entities = [];
        foreach ($schema->getRoles() as $role) {
            $entities[] = [
                $role,
                $schema->define($role, \Cycle\ORM\SchemaInterface::ENTITY),
                $schema->define($role, \Cycle\ORM\SchemaInterface::TABLE),
                $schema->define($role, \Cycle\ORM\SchemaInterface::DATABASE)
            ];
        }

        $this->table(['Role', 'Entity', 'Table', 'Database'], $entities);

        return self::SUCCESS;
    }
}
