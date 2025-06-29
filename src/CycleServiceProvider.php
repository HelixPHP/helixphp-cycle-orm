<?php
namespace CAFernandes\ExpressPHP\CycleORM;

use Express\Support\ServiceProvider;
use Express\Core\Application;
use Cycle\ORM\ORM;
use Cycle\ORM\Factory;
use Cycle\ORM\EntityManager;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Schema\Compiler;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Cycle\Schema\Generator;
use Spiral\Tokenizer\ClassesInterface;

/**
 * CORREÇÃO: Herda de Express\Support\ServiceProvider (arquitetura real)
 */
class CycleServiceProvider extends ServiceProvider
{
    /**
     * CORREÇÃO: Método register() compatível com Express-PHP v2.1.0
     */
    public function register(): void
    {
        $this->registerDatabaseManager();
        $this->registerSchemaCompiler();
        $this->registerORM();
        $this->registerEntityManager();
        $this->registerRepositoryFactory();
        $this->registerCommands();
    }

    /**
     * CORREÇÃO: Método boot() compatível com Express-PHP v2.1.0
     */
    public function boot(): void
    {
        $this->registerMiddlewares();
        $this->registerEventListeners();
        $this->publishAssets();

        // CORREÇÃO: Verificar environment usando método correto
        if ($this->app->isEnvironment('development')) {
            $this->enableDevelopmentFeatures();
        }
    }

    /**
     * CORREÇÃO: Database Manager com configuração mais robusta
     */
    private function registerDatabaseManager(): void
    {
        $this->app->singleton('cycle.database', function (Application $app) {
            $config = $app->config('cycle.database', $this->getDefaultDatabaseConfig());

            // CORREÇÃO: Validar configuração antes de usar
            $this->validateDatabaseConfig($config);

            return new DatabaseManager(new DatabaseConfig($config));
        });

        // CORREÇÃO: Alias correto
        $this->app->alias('cycle.database', 'db');
    }

    /**
     * CORREÇÃO: Schema Compiler com melhor tratamento de erros
     */
    private function registerSchemaCompiler(): void
    {
        $this->app->singleton('cycle.schema', function (Application $app) {
            try {
                $config = $app->config('cycle.entities', $this->getDefaultEntityConfig());

                // CORREÇÃO: Usar Spiral\Tokenizer corretamente
                $locator = new TokenizerEntityLocator(
                    $app->make(ClassesInterface::class),
                    $config['namespace']
                );

                $compiler = new Compiler();
                $this->addSchemaGenerators($compiler);

                return $compiler->compile($locator);
            } catch (\Exception $e) {
                // CORREÇÃO: Log de erro e fallback
                $app->logger()->error('Failed to compile Cycle schema', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw new \RuntimeException('Cycle schema compilation failed: ' . $e->getMessage());
            }
        });
    }

    /**
     * CORREÇÃO: ORM com cache e otimizações
     */
    private function registerORM(): void
    {
        $this->app->singleton('cycle.orm', function (Application $app) {
            $dbal = $app->make('cycle.database');
            $schema = $app->make('cycle.schema');

            $orm = new ORM(new Factory($dbal), $schema);

            // CORREÇÃO: Preparar serviços para melhor performance
            $orm->prepareServices();

            return $orm;
        });

        $this->app->alias('cycle.orm', 'orm');
    }

    /**
     * CORREÇÃO: Entity Manager com UnitOfWork otimizado
     */
    private function registerEntityManager(): void
    {
        $this->app->singleton('cycle.em', function (Application $app) {
            return new EntityManager($app->make('cycle.orm'));
        });

        $this->app->alias('cycle.em', 'em');
    }

    /**
     * CORREÇÃO: Repository Factory melhorado
     */
    private function registerRepositoryFactory(): void
    {
        $this->app->singleton('cycle.repository', function (Application $app) {
            return new RepositoryFactory($app->make('cycle.orm'));
        });
    }

    /**
     * CORREÇÃO: Comandos registrados corretamente
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\SchemaCommand::class,
                Commands\MigrateCommand::class,
                Commands\EntityCommand::class,
                Commands\SeedCommand::class,
                Commands\StatusCommand::class // NOVO: Status do schema
            ]);
        }
    }

    /**
     * CORREÇÃO: Middlewares registrados usando sistema correto do Express-PHP
     */
    private function registerMiddlewares(): void
    {
        // CORREÇÃO: Usar método correto para registrar middlewares
        $this->app->middleware('cycle', Middleware\CycleMiddleware::class);
        $this->app->middleware('transaction', Middleware\TransactionMiddleware::class);
        $this->app->middleware('entity-validation', Middleware\EntityValidationMiddleware::class);
    }

    /**
     * CORREÇÃO: Event listeners usando sistema PSR-14 do Express-PHP
     */
    private function registerEventListeners(): void
    {
        // CORREÇÃO: Usar addAction corretamente (sistema de hooks do Express-PHP)
        $this->app->addAction('cycle.query.executed', function ($context) {
            if ($this->app->isEnvironment('development')) {
                $this->app->logger()->debug('Cycle Query', [
                    'query' => $context['query'],
                    'bindings' => $context['bindings'] ?? [],
                    'time' => $context['time'] ?? 0
                ]);
            }
        });

        $this->app->addAction('cycle.entity.persisted', function ($context) {
            // CORREÇÃO: Invalidar cache corretamente
            if ($this->app->has('cache')) {
                $this->app->make('cache')->tags(['entities'])->flush();
            }
        });

        // NOVO: Hook para otimização automática
        $this->app->addAction('cycle.schema.compiled', function ($context) {
            $this->app->logger()->info('Cycle schema compiled successfully', [
                'entities' => count($context['entities'] ?? [])
            ]);
        });
    }

    /**
     * CORREÇÃO: Configurações padrão mais robustas
     */
    private function getDefaultDatabaseConfig(): array
    {
        return [
            'default' => env('DB_CONNECTION', 'mysql'),
            'databases' => [
                'default' => ['connection' => env('DB_CONNECTION', 'mysql')]
            ],
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => env('DB_HOST', 'localhost'),
                    'port' => (int) env('DB_PORT', 3306),
                    'database' => env('DB_DATABASE'),
                    'username' => env('DB_USERNAME'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'options' => [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_EMULATE_PREPARES => false,
                        \PDO::ATTR_STRINGIFY_FETCHES => false,
                    ]
                ],
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => env('DB_DATABASE', database_path('database.sqlite')),
                    'options' => [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    ]
                ]
            ]
        ];
    }

    /**
     * CORREÇÃO: Configuração de entidades mais flexível
     */
    private function getDefaultEntityConfig(): array
    {
        return [
            'directories' => [
                app_path('Models'),
                app_path('Entities')
            ],
            'namespace' => 'App\\Models'
        ];
    }

    /**
     * CORREÇÃO: Validação de configuração
     */
    private function validateDatabaseConfig(array $config): void
    {
        $required = ['default', 'databases', 'connections'];

        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("Missing required database config key: {$key}");
            }
        }

        $defaultConnection = $config['default'];
        if (!isset($config['connections'][$defaultConnection])) {
            throw new \InvalidArgumentException("Default connection '{$defaultConnection}' not found in connections config");
        }
    }

    /**
     * CORREÇÃO: Generators organizados e otimizados
     */
    private function addSchemaGenerators(Compiler $compiler): void
    {
        // CORREÇÃO: Ordem correta dos generators
        $generators = [
            Generator\ResetTables::class,
            \Cycle\Annotated\Embeddings::class,
            \Cycle\Annotated\Entities::class,
            \Cycle\Annotated\TableInheritance::class,
            \Cycle\Annotated\MergeColumns::class,
            Generator\GenerateRelations::class,
            Generator\ValidateEntities::class,
            Generator\RenderTables::class,
            Generator\RenderRelations::class,
            Generator\RenderModifiers::class,
            \Cycle\Annotated\MergeIndexes::class,
            Generator\GenerateTypecast::class,
        ];

        foreach ($generators as $generator) {
            $compiler->addGenerator($generator);
        }
    }

    /**
     * CORREÇÃO: Features de desenvolvimento
     */
    private function enableDevelopmentFeatures(): void
    {
        // Query logging mais detalhado
        $this->app->addAction('cycle.query.executed', function ($context) {
            $time = $context['time'] ?? 0;
            if ($time > 100) { // Log slow queries (>100ms)
                $this->app->logger()->warning('Slow Cycle Query', [
                    'query' => $context['query'],
                    'time' => $time . 'ms'
                ]);
            }
        });

        // Schema validation
        $this->app->addAction('application.booted', function () {
            $this->validateSchemaIntegrity();
        });
    }

    /**
     * NOVO: Validação de integridade do schema
     */
    private function validateSchemaIntegrity(): void
    {
        try {
            $orm = $this->app->make('cycle.orm');
            $schema = $orm->getSchema();

            // Verificar se há entidades registradas
            $roles = $schema->getRoles();
            if (empty($roles)) {
                $this->app->logger()->warning('No entities found in Cycle schema');
            }

            // Verificar integridade das relações
            foreach ($roles as $role) {
                $relations = $schema->define($role, \Cycle\ORM\SchemaInterface::RELATIONS) ?? [];
                foreach ($relations as $relation => $config) {
                    $target = $config[\Cycle\ORM\Relation::TARGET] ?? null;
                    if ($target && !in_array($target, $roles)) {
                        $this->app->logger()->error("Invalid relation target", [
                            'entity' => $role,
                            'relation' => $relation,
                            'target' => $target
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->app->logger()->error('Schema integrity check failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * CORREÇÃO: Publicação de assets
     */
    private function publishAssets(): void
    {
        // CORREÇÃO: Usar método correto do Express-PHP para publicar assets
        if (method_exists($this, 'publishes')) {
            $this->publishes([
                __DIR__ . '/../config/cycle.php' => config_path('cycle.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'migrations');
        }
    }
}