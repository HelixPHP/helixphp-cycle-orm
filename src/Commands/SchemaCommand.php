<?php
/**
 * Comando para schema - Versão corrigida
 */
class SchemaCommand extends BaseCommand
{
    public function handle(): int
    {
        if ($this->option('sync')) {
            return $this->syncSchema();
        }

        return $this->showSchema();
    }

    private function syncSchema(): int
    {
        $this->info('Synchronizing database schema...');

        try {
            // Verificar se função app() existe
            if (function_exists('app')) {
                $migrator = app('cycle.migrator');
            } else {
                $this->error('Application container not available');
                return 1;
            }

            // Executar migrações
            $migration = $migrator->run();

            if ($migration) {
                $this->info('Schema synchronized successfully!');
            } else {
                $this->info('Schema is already up to date.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to sync schema: ' . $e->getMessage());
            return 1;
        }
    }

    private function showSchema(): int
    {
        $this->info('Cycle ORM Schema Information');

        try {
            if (function_exists('app')) {
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
            } else {
                $this->error('Application container not available');
                return 1;
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to show schema: ' . $e->getMessage());
            return 1;
        }
    }
}