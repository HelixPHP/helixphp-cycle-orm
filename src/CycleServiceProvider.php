<?php
namespace ExpressPHP\CycleORM;

use Express\Core\Application;
use Express\Support\ServiceProvider;
use Cycle\ORM\ORM;
use Cycle\ORM\Factory;
use Cycle\ORM\EntityManager;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Schema\Compiler;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\Schema\Generator;

/**
 * Service Provider principal para Cycle ORM
 */
class CycleServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços no container
     */
    public function register(): void
    {
        $this->registerDatabaseManager();
        $this->registerSchemaCompiler();
        $this->registerORM();
        $this->registerEntityManager();
        $this->registerRepositoryFactory();
        $this->registerMigrations();
        $this->registerCommands();
    }

    /**
     * Inicializa os serviços após registro
     */
    public function boot(): void
    {
        $this->registerMiddlewares();
        $this->registerEventListeners();
        $this->publishConfiguration();

        // Auto-sync schema em desenvolvimento
        if ($this->app->environment('development')) {
            $this->syncSchemaIfNeeded();
        }
    }

    /**
     * Registra Database Manager
     */
    private function registerDatabaseManager(): void
    {
        $this->app->singleton('cycle.database', function ($app) {
            $config = $app->config('cycle.database', [
                'default' => 'mysql',
                'databases' => [
                    'default' => ['connection' => 'mysql']
                ],
                'connections' => [
                    'mysql' => [
                        'driver' => 'mysql',
                        'host' => env('DB_HOST', 'localhost'),
                        'port' => env('DB_PORT', 3306),
                        'database' => env('DB_DATABASE', 'express_db'),
                        'username' => env('DB_USERNAME', 'root'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => 'utf8mb4',
                        'options' => [
                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        ]
                    ]
                ]
            ]);

            return new DatabaseManager(new DatabaseConfig($config));
        });

        // Alias para facilitar acesso
        $this->app->alias('db', 'cycle.database');
    }

    /**
     * Registra Schema Compiler
     */
    private function registerSchemaCompiler(): void
    {
        $this->app->singleton('cycle.schema', function ($app) {
            $config = $app->config('cycle.entities', [
                'directories' => ['app/Models'],
                'namespace' => 'App\\Models'
            ]);

            $locator = new TokenizerEntityLocator(
                $config['directories'],
                $config['namespace']
            );

            $compiler = new Compiler();

            // Generators padrão do Cycle
            $compiler->addGenerator(Generator\ResetTables::class);
            $compiler->addGenerator(\Cycle\Annotated\Embeddings::class);
            $compiler->addGenerator(\Cycle\Annotated\Entities::class);
            $compiler->addGenerator(\Cycle\Annotated\TableInheritance::class);
            $compiler->addGenerator(\Cycle\Annotated\MergeColumns::class);
            $compiler->addGenerator(Generator\GenerateRelations::class);
            $compiler->addGenerator(Generator\ValidateEntities::class);
            $compiler->addGenerator(Generator\RenderTables::class);
            $compiler->addGenerator(Generator\RenderRelations::class);
            $compiler->addGenerator(Generator\RenderModifiers::class);
            $compiler->addGenerator(\Cycle\Annotated\MergeIndexes::class);
            $compiler->addGenerator(Generator\GenerateTypecast::class);

            return $compiler->compile($locator);
        });
    }

    /**
     * Registra ORM
     */
    private function registerORM(): void
    {
        $this->app->singleton('cycle.orm', function ($app) {
            $dbal = $app->make('cycle.database');
            $schema = $app->make('cycle.schema');

            return new ORM(new Factory($dbal), $schema);
        });

        $this->app->alias('orm', 'cycle.orm');
    }

    /**
     * Registra Entity Manager
     */
    private function registerEntityManager(): void
    {
        $this->app->singleton('cycle.em', function ($app) {
            return new EntityManager($app->make('cycle.orm'));
        });

        $this->app->alias('em', 'cycle.em');
    }

    /**
     * Registra Repository Factory
     */
    private function registerRepositoryFactory(): void
    {
        $this->app->singleton('cycle.repository', function ($app) {
            return new RepositoryFactory($app->make('cycle.orm'));
        });
    }

    /**
     * Registra sistema de migrações
     */
    private function registerMigrations(): void
    {
        $this->app->singleton('cycle.migrator', function ($app) {
            $dbal = $app->make('cycle.database');
            $config = $app->config('cycle.migrations', [
                'directory' => 'database/migrations',
                'table' => 'migrations'
            ]);

            return new \Cycle\Migrations\Migrator(
                $config,
                $dbal,
                new \Cycle\Migrations\FileRepository($config)
            );
        });
    }

    /**
     * Registra comandos CLI
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\SchemaCommand::class,
                Commands\MigrateCommand::class,
                Commands\EntityCommand::class,
                Commands\SeedCommand::class
            ]);
        }
    }

    /**
     * Registra middlewares
     */
    private function registerMiddlewares(): void
    {
        // Middleware para injeção automática do ORM
        $this->app->middleware('cycle', Middleware\CycleMiddleware::class);

        // Middleware para transações automáticas
        $this->app->middleware('transaction', Middleware\TransactionMiddleware::class);

        // Middleware para validação de entidades
        $this->app->middleware('validate-entity', Middleware\EntityValidationMiddleware::class);
    }

    /**
     * Registra event listeners
     */
    private function registerEventListeners(): void
    {
        // Log de queries em desenvolvimento
        if ($this->app->environment('development')) {
            $this->app->addAction('cycle.query', function ($context) {
                $this->app->logger()->debug('Cycle Query', [
                    'query' => $context['query'],
                    'bindings' => $context['bindings'],
                    'time' => $context['time']
                ]);
            });
        }

        // Cache busting em mudanças de entidades
        $this->app->addAction('cycle.entity.persisted', function ($context) {
            $this->app->cache()->tags(['entities'])->flush();
        });
    }

    /**
     * Publica configurações
     */
    private function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__ . '/../config/cycle.php' => config_path('cycle.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');
    }

    /**
     * Sincroniza schema se necessário
     */
    private function syncSchemaIfNeeded(): void
    {
        $config = $this->app->config('cycle.schema');

        if ($config['auto_sync'] ?? false) {
            try {
                $migrator = $this->app->make('cycle.migrator');
                $migrator->run();
            } catch (\Exception $e) {
                $this->app->logger()->warning('Failed to auto-sync schema: ' . $e->getMessage());
            }
        }
    }
}
