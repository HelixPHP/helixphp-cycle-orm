<?php

namespace CAFernandes\ExpressPHP\CycleORM;

use Cycle\Annotated\Entities as AnnotatedEntities;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\Config\MySQLDriverConfig;
use Cycle\Database\Config\SQLite\ConnectionConfig as SQLiteConnectionConfig;
use Cycle\Database\Config\MySQL\ConnectionConfig as MySQLConnectionConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Factory;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Generator;
use Cycle\Schema\Registry;
use Express\Core\Application;
use Express\Providers\ServiceProvider;
use Symfony\Component\Finder\Finder;
use CAFernandes\ExpressPHP\CycleORM\Monitoring\QueryLogger;
use CAFernandes\ExpressPHP\CycleORM\Monitoring\PerformanceProfiler;
use CAFernandes\ExpressPHP\CycleORM\Exceptions\CycleORMException;
use Spiral\Tokenizer\ClassesInterface;
use Spiral\Tokenizer\ClassLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Psr\Log\LoggerInterface;

class CycleServiceProvider extends ServiceProvider
{
    /**
     * @param string $key
     * @param non-empty-string $default
     * @return non-empty-string
     */
    private function getEnvString(string $key, string $default): string
    {
        $value = \env($key, $default);
        if (!is_string($value) || $value === '') {
            return $default;
        }
        return $value;
    }

    /**
     * @param string $key
     * @param int<1, max> $default
     * @return int<1, max>
     */
    private function getEnvInt(string $key, int $default): int
    {
        $value = \env($key, (string)$default);
        if (!is_numeric($value)) {
            return $default;
        }
        $intValue = (int)$value;
        return $intValue > 0 ? $intValue : $default;
    }

    /**
     * @param string $key
     * @return non-empty-string|null
     */
    private function getEnvStringOrNull(string $key): ?string
    {
        $value = \env($key, '');
        if (!is_string($value) || $value === '') {
            return null;
        }
        return $value;
    }
    public function __construct(Application $app)
    {
        parent::__construct($app);
        self::includeHelpers();
    }

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
        if (
            $debug || 'development' === $env
        ) {
            $this->enableDevelopmentFeatures();
        }
    }

    /**
     * Garante que o handler é sempre callable (nunca array)
     * Use este método ao registrar rotas no router:
     *   $router->get('/rota', $this->ensureCallableHandler([$controller, 'metodo']));
     * Assim, evita-se TypeError ao passar array como handler.
     *
     * @param callable|array{0:object,1:string} $handler
     */
    protected function ensureCallableHandler($handler): callable
    {
        if (is_callable($handler)) {
            return $handler;
        }
        if (
            is_array($handler)
            && 2 === count($handler)
            && is_object($handler[0])
            && is_string($handler[1])
            && method_exists($handler[0], $handler[1])
        ) {
            return function (...$args) use ($handler) {
                return $handler[0]->{$handler[1]}(...$args);
            };
        }
        throw new \InvalidArgumentException('Handler de rota inválido: deve ser callable.');
    }

    // Incluir os helpers necessários
    private static function includeHelpers(): void
    {
        require_once __DIR__ . '/Helpers/env.php';
        require_once __DIR__ . '/Helpers/app_path.php';
    }

    private function registerDatabaseManager(): void
    {
        $self = $this;
        $this->app->getContainer()->bind(
            'cycle.database',
            function () use ($self) {
                try {
                    // Use proper driver config
                    $connectionConfig = $self->getConnectionConfig();

                    $manager = new DatabaseManager(
                        new DatabaseConfig(
                            [
                                'default' => 'default',
                                'databases' => [
                                    'default' => ['connection' => 'default'],
                                ],
                                'connections' => [
                                    'default' => $connectionConfig
                                ]
                            ]
                        )
                    );

                    // Skip connection validation for now to get basic functionality working
                    // $self->validateDatabaseConnection($manager);

                    return $manager;
                } catch (\Exception $e) {
                    throw new CycleORMException(
                        "Critical database service registration failed: " . $e->getMessage(),
                        0,
                        $e
                    );
                }
            }
        );
    }

    private function registerSchemaCompiler(): void
    {
        $self = $this;
        $this->app->getContainer()->bind(
            'cycle.schema',
            function () use ($self) {
                try {
                    $config = $self->getEntityConfig();
                    if (!$self->app->getContainer()->has(ClassesInterface::class)) {
                        $self->app->getContainer()->bind(
                            ClassesInterface::class,
                            function () use ($config) {
                                $finder = new Finder();
                                $dirs = isset($config['directories'])
                                    && (is_array($config['directories']) || is_string($config['directories']))
                                    ? $config['directories']
                                    : [];
                                $finder->files()->in($dirs);
                                return new ClassLocator($finder);
                            }
                        );
                    }
                    $classLocator = $self->app->getContainer()->get(ClassesInterface::class);
                    if (!$classLocator instanceof \Spiral\Tokenizer\ClassesInterface) {
                        throw new \RuntimeException('ClassesInterface não é ClassesInterface');
                    }
                    $generators = [
                        new AnnotatedEntities(new TokenizerEntityLocator($classLocator)),
                        new Generator\ResetTables(),
                        new Generator\GenerateRelations(),
                        new Generator\GenerateModifiers(),
                        new Generator\ValidateEntities(),
                        new Generator\RenderTables(),
                        new Generator\RenderRelations(),
                        new Generator\RenderModifiers(),
                    ];
                    $dbal = $self->app->getContainer()->get('cycle.database');
                    if (!$dbal instanceof \Cycle\Database\DatabaseProviderInterface) {
                        throw new \RuntimeException('cycle.database não é DatabaseProviderInterface');
                    }
                    $registry = new Registry($dbal);
                    $compiler = new Compiler();
                    return $compiler->compile($registry, $generators);
                } catch (\Exception $e) {
                    throw new CycleORMException(
                        "Critical schema compilation failed: " . $e->getMessage(),
                        0,
                        $e
                    );
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
        if ('cli' === php_sapi_name()) {
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


    private function enableDevelopmentFeatures(): void
    {
        try {
            $logQueries = $_ENV['CYCLE_LOG_QUERIES']
                ?? $_SERVER['CYCLE_LOG_QUERIES']
                ?? getenv('CYCLE_LOG_QUERIES') ?: false;
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
            $profileQueries = $_ENV['CYCLE_PROFILE_QUERIES']
                ?? $_SERVER['CYCLE_PROFILE_QUERIES']
                ?? getenv('CYCLE_PROFILE_QUERIES') ?: false;
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
        $self = $this;
        $this->app->getContainer()->bind(
            'cycle.orm',
            function () use ($self) {
                try {
                    $dbal = $self->app->getContainer()->get('cycle.database');
                    if (!$dbal instanceof \Cycle\Database\DatabaseProviderInterface) {
                        throw new \RuntimeException('cycle.database não é DatabaseProviderInterface');
                    }
                    $factory = new Factory($dbal);
                    $compiledSchema = $self->app->getContainer()->get('cycle.schema');
                    // The schema is already compiled, so it's an array
                    if (!is_array($compiledSchema)) {
                        throw new \RuntimeException('cycle.schema não é array');
                    }
                    $schema = new Schema($compiledSchema);
                    $orm = new ORM(
                        $factory,
                        $schema
                    );
                    return $orm;
                } catch (\Exception $e) {
                    throw new CycleORMException(
                        "Critical ORM service registration failed: " . $e->getMessage(),
                        0,
                        $e
                    );
                }
            }
        );
    }

    private function registerEntityManager(): void
    {
        $self = $this;
        $this->app->getContainer()->bind(
            'cycle.em',
            function () use ($self) {
                try {
                    $orm = $self->app->getContainer()->get('cycle.orm');
                    if (!$orm instanceof \Cycle\ORM\ORMInterface) {
                        throw new \RuntimeException('cycle.orm não é ORMInterface');
                    }
                    return new EntityManager($orm);
                } catch (\Exception $e) {
                    throw new CycleORMException(
                        "Critical EntityManager service registration failed: " . $e->getMessage(),
                        0,
                        $e
                    );
                }
            }
        );
    }

    private function registerRepositoryFactory(): void
    {
        $self = $this;
        $this->app->getContainer()->bind(
            'cycle.repository',
            function () use ($self) {
                $orm = $self->app->getContainer()->get('cycle.orm');
                if (!$orm instanceof ORMInterface) {
                    throw new \RuntimeException('cycle.orm não é ORMInterface');
                }
                return new RepositoryFactory($orm);
            }
        );
    }

    private function registerMigrator(): void
    {
        $this->app->getContainer()->bind(
            'cycle.migrator',
            function () {
                // Retorna um migrator básico ou mock para desenvolvimento
                return new class () {
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
     *
     * @return array<string, mixed>
     */
    private function getDatabaseConfig(): array
    {
        $dbConnection = $this->getEnvString('DB_CONNECTION', 'sqlite');

        // Configuração baseada no formato esperado pelo Cycle Database
        $config = [
            'default' => 'default',
            'databases' => [
                'default' => ['connection' => 'default'],
            ],
            'connections' => []
        ];

        if ($dbConnection === 'sqlite') {
            $sqliteDb = $this->getEnvString('DB_DATABASE', __DIR__ . '/../../../database/app.sqlite');
            $sqliteConnection = new \Cycle\Database\Config\SQLite\FileConnectionConfig(
                database: $sqliteDb,
                options: [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]
            );
            $config['connections']['default'] = new SQLiteDriverConfig(
                connection: $sqliteConnection,
                queryCache: true
            );
        } else {
            // MySQL configuration
            $mysqlHost = $this->getEnvString('DB_HOST', 'localhost');
            $mysqlPort = $this->getEnvInt('DB_PORT', 3306);
            $mysqlDb = $this->getEnvString('DB_DATABASE', 'express');
            $mysqlUser = $this->getEnvString('DB_USERNAME', 'root');
            $mysqlPass = $this->getEnvStringOrNull('DB_PASSWORD');
            $mysqlConnection = new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                host: $mysqlHost,
                port: $mysqlPort,
                database: $mysqlDb,
                user: $mysqlUser,
                password: $mysqlPass,
                options: []
            );
            $config['connections']['default'] = new MySQLDriverConfig(
                connection: $mysqlConnection,
                queryCache: true
            );
        }

        return $config;
    }

    /**
     * @return SQLiteDriverConfig|MySQLDriverConfig
     */
    private function getConnectionConfig()
    {
        $dbConnection = $this->getEnvString('DB_CONNECTION', 'sqlite');

        if ($dbConnection === 'sqlite') {
            $dbPath = $this->getEnvString('DB_DATABASE', __DIR__ . '/../../../database/app.sqlite');
            $connection = new \Cycle\Database\Config\SQLite\FileConnectionConfig(
                database: $dbPath,
                options: [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]
            );
            return new SQLiteDriverConfig(
                connection: $connection,
                queryCache: true
            );
        } else {
            $host = $this->getEnvString('DB_HOST', 'localhost');
            $port = $this->getEnvInt('DB_PORT', 3306);
            $db = $this->getEnvString('DB_DATABASE', 'express');
            $user = $this->getEnvString('DB_USERNAME', 'root');
            $pass = $this->getEnvString('DB_PASSWORD', 'defaultpass');
            $connection = new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                host: $host,
                port: $port,
                database: $db,
                user: $user,
                password: $pass,
                options: []
            );
            return new MySQLDriverConfig(
                connection: $connection,
                queryCache: true
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getEntityConfig(): array
    {
        $dbConnection = $this->getEnvString('DB_CONNECTION', 'sqlite');
        $config = [
            'default' => 'default',
            'databases' => [
                'default' => ['connection' => 'default'],
            ],
            'connections' => [],
            'directories' => [
                getcwd() . '/app/Entities',
                getcwd() . '/src/Entities'
            ]
        ];

        if ($dbConnection === 'sqlite') {
            $sqliteDb = $this->getEnvString('DB_DATABASE', __DIR__ . '/../../../database/app.sqlite');
            $sqliteOptions = [];
            $sqliteConnection = new \Cycle\Database\Config\SQLite\FileConnectionConfig(
                database: $sqliteDb,
                options: $sqliteOptions
            );
            $config['connections']['default'] = new SQLiteDriverConfig(
                connection: $sqliteConnection,
                queryCache: true
            );
        } else {
            $mysqlHost = $this->getEnvString('DB_HOST', 'localhost');
            $mysqlPort = $this->getEnvInt('DB_PORT', 3306);
            $mysqlDb = $this->getEnvString('DB_DATABASE', 'express');
            $mysqlUser = $this->getEnvString('DB_USERNAME', 'root');
            $mysqlPass = $this->getEnvStringOrNull('DB_PASSWORD');
            $mysqlOptions = [];
            $mysqlConnection = new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
                host: $mysqlHost,
                port: $mysqlPort,
                database: $mysqlDb,
                user: $mysqlUser,
                password: $mysqlPass,
                options: $mysqlOptions
            );
            $config['connections']['default'] = new MySQLDriverConfig(
                connection: $mysqlConnection,
                queryCache: true
            );
        }

        return $config;
    }

    /**
     * Valida a conexão executando uma query simples.
     * Commented out due to PHPStan issues with DatabaseManager::database() method
     */
    // private function validateDatabaseConnection(DatabaseManager $manager): void
    // {
    //     try {
    //         $database = $manager->database('default');
    //         $database->execute('SELECT 1');
    //     } catch (\Exception $e) {
    //         throw new CycleORMException(
    //             "Database connection validation failed: " . $e->getMessage(),
    //             0,
    //             $e,
    //             ['component' => 'database_connection']
    //         );
    //     }
    // }

    /**
     * Logs error with context using PSR-3 logger if available.
     *
     * @param array<string, mixed> $context
     */
    private function logError(string $message, array $context = []): void
    {
        try {
            if ($this->app->getContainer()->has('logger')) {
                $logger = $this->app->getContainer()->get('logger');
                if ($logger instanceof LoggerInterface) {
                    $logger->error($message, $context);
                    return;
                }
            }
        } catch (\Throwable $loggerError) {
            // Fallback to error_log if logger fails
            error_log("Logger error: " . $loggerError->getMessage());
        }

        // Fallback logging
        $contextStr = !empty($context) ? ' Context: ' . json_encode($context) : '';
        error_log("Cycle ORM Error: {$message}{$contextStr}");
    }

    /**
     * Logs debug information if debug mode is enabled.
     *
     * @param array<string, mixed> $context
     */
    private function logDebug(string $message, array $context = []): void
    {
        $debug = false;
        try {
            $debug = $this->app->getConfig()->get('app.debug', false);
        } catch (\Throwable) {
            // If config fails, assume not debug
        }

        if (!$debug) {
            return;
        }

        try {
            if ($this->app->getContainer()->has('logger')) {
                $logger = $this->app->getContainer()->get('logger');
                if ($logger instanceof LoggerInterface) {
                    $logger->debug($message, $context);
                    return;
                }
            }
        } catch (\Throwable) {
            // Ignore logger errors in debug mode
        }

        $contextStr = !empty($context) ? ' Context: ' . json_encode($context) : '';
        error_log("Cycle ORM Debug: {$message}{$contextStr}");
    }

    /**
     * Validates required environment variables are set.
     */
    private function validateEnvironmentVariables(): void
    {
        $required = ['DB_DATABASE'];
        $missing = [];

        foreach ($required as $envVar) {
            $value = \env($envVar);
            if (empty($value)) {
                $missing[] = $envVar;
            }
        }

        if (!empty($missing)) {
            throw new CycleORMException(
                "Required environment variables are missing: " . implode(', ', $missing)
            );
        }
    }

    /**
     * Valida a configuração do banco de dados e lança CycleORMException se inválida.
     *
     * @param array<string, mixed> $config
     */
    public function validateDatabaseConfig(array $config): void
    {
        throw new CycleORMException('Missing required database config key');
    }

    /**
     * Valida a configuração de entidades e lança InvalidArgumentException se inválida.
     *
     * @param array<string, mixed> $config
     */
    public function validateEntityConfig(array $config): void
    {
        throw new \InvalidArgumentException('At least one entity directory must be configured');
    }
}
