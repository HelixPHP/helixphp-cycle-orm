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

class CycleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerDatabaseManager();
        $this->registerSchemaCompiler();
        $this->registerORM();
        $this->registerEntityManager();
        $this->registerRepositoryFactory();
    }

    public function boot(): void
    {
        $this->registerMiddlewares();
        $this->registerCommands();

        // Só ativar features de dev se estiver em modo debug
        if (config('app.debug', false) || env('APP_ENV') === 'development') {
            $this->enableDevelopmentFeatures();
        }
    }

    private function registerDatabaseManager(): void
    {
        $this->app->singleton('cycle.database', function (Application $app) {
            $config = $this->getDatabaseConfig();
            $this->validateDatabaseConfig($config);
            return new DatabaseManager(new DatabaseConfig($config));
        });

        $this->app->alias('cycle.database', 'db');
    }

    private function registerSchemaCompiler(): void
    {
        $this->app->singleton('cycle.schema', function (Application $app) {
            try {
                $config = $this->getEntityConfig();

                // Criar tokenizer se não existir
                if (!$app->has(ClassesInterface::class)) {
                    $app->singleton(ClassesInterface::class, function() use ($config) {
                        return new \Spiral\Tokenizer\Classes(
                            new \Spiral\Tokenizer\ClassLocator(
                                new \Spiral\Tokenizer\Reflection\ReflectionFile()
                            )
                        );
                    });
                }

                $locator = new TokenizerEntityLocator(
                    $app->make(ClassesInterface::class),
                    $config['namespace']
                );

                $compiler = new Compiler();
                $this->addSchemaGenerators($compiler);

                return $compiler->compile($locator);
            } catch (\Exception $e) {
                $this->logError('Failed to compile Cycle schema: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    private function registerMiddlewares(): void
    {
        // Express-PHP usa middleware registration diferente
        $this->app->booted(function($app) {
            // Registrar middlewares quando app estiver pronto
            if (method_exists($app, 'use')) {
                $app->use(new Middleware\CycleMiddleware($app));
                $app->use(new Middleware\TransactionMiddleware($app));
            }
        });
    }

    private function registerCommands(): void
    {
        // Só registrar commands se estiver em CLI
        if (php_sapi_name() === 'cli') {
            // Implementar sistema de commands específico do Express-PHP
            $this->app->singleton('cycle.commands', function() {
                return [
                    'cycle:schema' => Commands\SchemaCommand::class,
                    'cycle:migrate' => Commands\MigrateCommand::class,
                    'make:entity' => Commands\EntityCommand::class,
                ];
            });
        }
    }

    private function getDatabaseConfig(): array
    {
        return config('cycle.database', [
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
                    ]
                ]
            ]
        ]);
    }

    private function getEntityConfig(): array
    {
        return config('cycle.entities', [
            'directories' => [
                app_path('Models'),
            ],
            'namespace' => 'App\\Models'
        ]);
    }

    private function logError(string $message): void
    {
        if (method_exists($this->app, 'logger') && $this->app->has('logger')) {
            $this->app->logger()->error($message);
        } else {
            error_log($message);
        }
    }
}