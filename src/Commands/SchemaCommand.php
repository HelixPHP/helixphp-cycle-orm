<?php
namespace CAFernandes\ExpressPHP\CycleORM\Commands;

/**
 * Comando para schema - Versão corrigida
 */
class SchemaCommand extends BaseCommand
{
    protected $app;
    public function __construct(array $args = [], $app = null)
    {
        parent::__construct($args);
        $this->app = $app;
    }

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
            if ($this->app && method_exists($this->app, 'container')) {
                $migrator = $this->app->container()->get('cycle.migrator');
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
            if ($this->app && method_exists($this->app, 'container')) {
                $orm = $this->app->container()->get('cycle.orm');
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