<?php

namespace CAFernandes\ExpressPHP\CycleORM;

use Express\Providers\ExtensionServiceProvider;
use Cycle\Schema\Generator;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Schema\Compiler;
use Spiral\Tokenizer\ClassesInterface;
use Spiral\Tokenizer\ClassLocator;
use CAFernandes\ExpressPHP\CycleORM\Monitoring\QueryLogger;
use CAFernandes\ExpressPHP\CycleORM\Monitoring\PerformanceProfiler;
use Symfony\Component\Finder\Finder;
use Cycle\Schema\Registry;
use Cycle\Annotated\Entities as AnnotatedEntities;

// Incluir os helpers necessários
require_once __DIR__ . '/Helpers/env.php';
require_once __DIR__ . '/Helpers/config.php';
require_once __DIR__ . '/Helpers/app_path.php';

class CycleServiceProvider extends ExtensionServiceProvider
{
  /**
   * @var \Express\Core\Application
   */
    protected \Express\Core\Application $app;

    public function register(): void
    {
        $this->registerDatabaseManager();
        $this->registerSchemaCompiler();
        $this->registerORM();
        $this->registerEntityManager();
        $this->registerRepositoryFactory();
        $this->registerMigrator();
    }

    public function boot(): void
    {
        $this->registerMiddlewares();
        $this->registerCommands();

      // Verificar se devemos habilitar funcionalidades de desenvolvimento
      // Usa funções globais para evitar problemas de inicialização
        $debug = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false;
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
        if ($debug || $env === 'development') {
            $this->enableDevelopmentFeatures();
        }
    }

    private function registerDatabaseManager(): void
    {
        $this->app->getContainer()->bind(
            'cycle.database',
            function ($app) {
                $config = $this->getDatabaseConfig();
                $this->validateDatabaseConfig($config);
                return new DatabaseManager(new DatabaseConfig($config));
            }
        );
    }

    private function registerSchemaCompiler(): void
    {
        $this->app->getContainer()->bind(
            'cycle.schema',
            function ($app) {
                try {
                    $config = $this->getEntityConfig();
                    if (!$app->getContainer()->has(ClassesInterface::class)) {
                        $app->getContainer()->bind(
                            ClassesInterface::class,
                            function () use ($config) {
                                $finder = new Finder();
                                $dirs =
                                isset($config['directories']) &&
                                (
                                is_array($config['directories']) || is_string($config['directories'])
                                ) ? $config['directories'] : [];
                                $finder->files()->in($dirs);
                                return new ClassLocator($finder);
                            }
                        );
                    }
                    $classLocator = $app->getContainer()->get(ClassesInterface::class);
                    $registry = new Registry($app->getContainer()->get('cycle.database'));
                    $generators = [
                        new AnnotatedEntities($classLocator),
                        new Generator\ResetTables(),
                        new Generator\GenerateRelations(),
                        new Generator\GenerateModifiers(),
                        new Generator\ValidateEntities(),
                        new Generator\RenderTables(),
                        new Generator\RenderRelations(),
                        new Generator\RenderModifiers(),
                    ];
                    $compiler = new Compiler();
                    $schema = $compiler->compile($registry, $generators);
                    return $schema;
                } catch (\Exception $e) {
                    $this->logError('Failed to compile Cycle schema: ' . $e->getMessage());
                    throw $e;
                }
            }
        );
    }

    private function registerMiddlewares(): void
    {
        if (method_exists($this->app, 'use')) {
            $this->app->use(new Middleware\CycleMiddleware($this->app));
            $this->app->use(new Middleware\TransactionMiddleware($this->app));
        }
    }

    private function registerCommands(): void
    {
        if (php_sapi_name() === 'cli') {
            $this->app->getContainer()->bind(
                'cycle.commands',
                function () {
                    return [
                        'cycle:schema' => Commands\SchemaCommand::class,
                        'cycle:migrate' => Commands\MigrateCommand::class,
                        'make:entity' => Commands\EntityCommand::class,
                    ];
                }
            );
        }
    }

    private function logError(string $message): void
    {
        if ($this->app->getContainer()->has('logger')) {
            $logger = $this->app->getContainer()->get('logger');
            if (is_object($logger) && method_exists($logger, 'error')) {
                $logger->error($message);
            }
        } else {
            error_log($message);
        }
    }

    private function enableDevelopmentFeatures(): void
    {
        try {
            $logQueries = $_ENV['CYCLE_LOG_QUERIES'] ?? $_SERVER['CYCLE_LOG_QUERIES'] ?? getenv('CYCLE_LOG_QUERIES') ?: false;
            if ($logQueries) {
                $this->app->getContainer()->bind(
                    'cycle.query_logger',
                    function () {
                        return new QueryLogger();
                    }
                );
            }
        } catch (\Exception $e) {
            $this->logError('Failed to enable query logging: ' . $e->getMessage());
        }

        try {
            $profileQueries =
              $_ENV['CYCLE_PROFILE_QUERIES'] ??
              $_SERVER['CYCLE_PROFILE_QUERIES'] ??
              getenv('CYCLE_PROFILE_QUERIES') ?: false;
            if ($profileQueries) {
                $this->app->getContainer()->bind(
                    'cycle.profiler',
                    function () {
                        return new PerformanceProfiler();
                    }
                );
            }
        } catch (\Exception $e) {
            $this->logError('Failed to enable query profiling: ' . $e->getMessage());
        }
    }

    private function registerORM(): void
    {
        $this->app->getContainer()->bind(
            'cycle.orm',
            function ($app) {
                $factory = new Factory(
                    $app->getContainer()->get('cycle.database')
                );
                return new ORM(
                    $factory,
                    $app->getContainer()->get('cycle.schema')
                );
            }
        );
    }

    private function registerEntityManager(): void
    {
        $this->app->getContainer()->bind(
            'cycle.em',
            function ($app) {
                return new EntityManager($app->getContainer()->get('cycle.orm'));
            }
        );
    }

    private function registerRepositoryFactory(): void
    {
        $this->app->getContainer()->bind(
            'cycle.repository',
            function ($app) {
                return new RepositoryFactory($app->getContainer()->get('cycle.orm'));
            }
        );
    }

    private function registerMigrator(): void
    {
        $this->app->getContainer()->bind(
            'cycle.migrator',
            function ($app) {
                // Retorna um migrator básico ou mock para desenvolvimento
                return new class {
                  /**
                   * @return array{pending: array<int, mixed>, executed: array<int, mixed>}
                   */
                    public function getStatus(): array
                    {
                        return ['pending' => [], 'executed' => []];
                    }

                    public function run(): void
                    {
                      // Mock implementation
                    }

                    public function rollback(): void
                    {
                      // Mock implementation
                    }
                };
            }
        );
    }

  /**
   * Retorna a configuração do banco de dados.
   * @return array<string, mixed>
   */
    private function getDatabaseConfig(): array
    {
        $result = config(
            'cycle.database',
            [
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
            ]
        );
        return is_array($result) ? $result : [];
    }

  /**
   * Retorna a configuração das entidades.
   * @return array<string, mixed>
   */
    private function getEntityConfig(): array
    {
        $result = config(
            'cycle.entities',
            [
                'directories' => [
                    app_path('Models'),
                ],
                'namespace' => 'App\\Models'
            ]
        );
        return is_array($result) ? $result : [];
    }

  /**
   * Valida a configuração do banco de dados.
   * @param array<string, mixed> $config
   */
    private function validateDatabaseConfig(array $config): void
    {
        $required = ['default', 'databases', 'connections'];

        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("Missing required database config key: {$key}");
            }
        }

        $default = $config['default'];
        $defaultStr = (is_string($default) || is_numeric($default)) ? (string)$default : '';
        if (!isset($config['connections']) || !is_array($config['connections']) || !isset($config['connections'][$defaultStr])) {
            throw new \InvalidArgumentException("Default connection '" . $defaultStr . "' not configured");
        }
    }

  /**
   * Valida a configuração das entidades.
   * @param array<string, mixed> $config
   */
    private function validateEntityConfig(array $config): void
    {
        if (!isset($config['directories']) || !is_array($config['directories']) || empty($config['directories'])) {
            throw new \InvalidArgumentException('At least one entity directory must be configured');
        }

        foreach ($config['directories'] as $dir) {
            if (!is_string($dir)) {
                throw new \InvalidArgumentException('Entity directory must be a string');
            }
            if (!is_dir($dir)) {
              // Criar diretório se não existir
                if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                    throw new \InvalidArgumentException("Entity directory cannot be created: {$dir}");
                }
            }
        }
    }

  /**
   * Garante que o handler é sempre callable (nunca array)
   * Use este método ao registrar rotas no router:
   *   $router->get('/rota', $this->ensureCallableHandler([$controller, 'metodo']));
   * Assim, evita-se TypeError ao passar array como handler.
   *
   * @param mixed $handler
   * @return callable
   */
    protected function ensureCallableHandler($handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }
        if (is_array($handler) && count($handler) === 2 && is_object($handler[0]) && is_string($handler[1]) && method_exists($handler[0], $handler[1])) {
            return function (...$args) use ($handler) {
                return $handler[0]->{$handler[1]}(...$args);
            };
        }
        throw new \InvalidArgumentException('Handler de rota inválido: deve ser callable.');
    }
}
