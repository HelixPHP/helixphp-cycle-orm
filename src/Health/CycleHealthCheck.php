<?php

namespace CAFernandes\ExpressPHP\CycleORM\Health;

use Express\Core\Application;
use Cycle\ORM\ORM;
use Cycle\Database\DatabaseManager;

/**
 * Sistema de Health Check para Cycle ORM
 */
class CycleHealthCheck
{
    /**
     * Verificar saúde geral do sistema Cycle ORM
     */
    public static function check(Application $app): array
    {
        $startTime = microtime(true);
        $status = [
            'cycle_orm' => 'healthy',
            'timestamp' => date('c'),
            'checks' => []
        ];

        try {
            // Verificar serviços registrados
            $status['checks']['services'] = self::checkServices($app);

            // Verificar conexão com database
            $status['checks']['database'] = self::checkDatabase($app);

            // Verificar schema
            $status['checks']['schema'] = self::checkSchema($app);

            // Verificar performance
            $status['checks']['performance'] = self::checkPerformance($app, $startTime);

            // Status geral
            $allHealthy = true;
            foreach ($status['checks'] as $check) {
                if ($check['status'] !== 'healthy') {
                    $allHealthy = false;
                    break;
                }
            }

            $status['cycle_orm'] = $allHealthy ? 'healthy' : 'unhealthy';

        } catch (\Exception $e) {
            $status['cycle_orm'] = 'unhealthy';
            $status['error'] = $e->getMessage();
        }

        $status['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        return $status;
    }

    /**
     * Verificar serviços registrados
     */
    private static function checkServices(Application $app): array
    {
        $services = [
            'cycle.database' => 'Database Manager',
            'cycle.orm' => 'ORM',
            'cycle.em' => 'Entity Manager',
            'cycle.schema' => 'Schema',
            'cycle.migrator' => 'Migrator',
            'cycle.repository' => 'Repository Factory'
        ];

        $registered = [];
        $missing = [];

        foreach ($services as $service => $name) {
            if ($app->has($service)) {
                $registered[] = $name;
            } else {
                $missing[] = $name;
            }
        }

        return [
            'status' => empty($missing) ? 'healthy' : 'unhealthy',
            'registered' => $registered,
            'missing' => $missing,
            'total_services' => count($services)
        ];
    }

    /**
     * Verificar conexão com database
     */
    private static function checkDatabase(Application $app): array
    {
        try {
            if (!$app->has('cycle.database')) {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Database manager not available'
                ];
            }

            /** @var DatabaseManager $dbal */
            $dbal = $app->make('cycle.database');
            $db = $dbal->database();

            // Testar conexão
            $startTime = microtime(true);
            $pdo = $db->getDriver()->getPDO();
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Informações da conexão
            $driver = $db->getDriver();
            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

            // Testar query simples
            $startTime = microtime(true);
            $result = $pdo->query('SELECT 1')->fetchColumn();
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => $result === '1' ? 'healthy' : 'unhealthy',
                'driver' => $driver->getType(),
                'version' => $version,
                'connection_time_ms' => $connectionTime,
                'query_time_ms' => $queryTime
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar schema
     */
    private static function checkSchema(Application $app): array
    {
        try {
            if (!$app->has('cycle.orm')) {
                return [
                    'status' => 'unhealthy',
                    'error' => 'ORM not available'
                ];
            }

            /** @var ORM $orm */
            $orm = $app->make('cycle.orm');
            $schema = $orm->getSchema();

            $roles = $schema->getRoles();
            $entitiesInfo = [];

            foreach ($roles as $role) {
                $entityClass = $schema->define($role, \Cycle\ORM\SchemaInterface::ENTITY);
                $table = $schema->define($role, \Cycle\ORM\SchemaInterface::TABLE);
                $database = $schema->define($role, \Cycle\ORM\SchemaInterface::DATABASE) ?? 'default';

                $entitiesInfo[] = [
                    'role' => $role,
                    'entity' => $entityClass,
                    'table' => $table,
                    'database' => $database
                ];
            }

            return [
                'status' => 'healthy',
                'entities_count' => count($roles),
                'entities' => $entitiesInfo
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar performance
     */
    private static function checkPerformance(Application $app, float $startTime): array
    {
        $performance = [
            'status' => 'healthy',
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        // Verificar tempo de resposta
        $responseTime = (microtime(true) - $startTime) * 1000;
        $performance['response_time_ms'] = round($responseTime, 2);

        // Alertas de performance
        $warnings = [];

        if ($responseTime > 1000) {
            $warnings[] = 'Slow health check response time';
            $performance['status'] = 'warning';
        }

        if ($performance['memory_usage_mb'] > 128) {
            $warnings[] = 'High memory usage';
            $performance['status'] = 'warning';
        }

        if (!empty($warnings)) {
            $performance['warnings'] = $warnings;
        }

        return $performance;
    }

    /**
     * Health check detalhado para debugging
     */
    public static function detailedCheck(Application $app): array
    {
        $basicCheck = self::check($app);

        // Adicionar informações detalhadas
        $basicCheck['detailed'] = [
            'php_version' => PHP_VERSION,
            'cycle_version' => self::getCycleVersion(),
            'pdo_drivers' => \PDO::getAvailableDrivers(),
            'loaded_extensions' => self::getRelevantExtensions(),
            'configuration' => self::getConfiguration($app),
            'environment' => [
                'app_env' => env('APP_ENV', 'unknown'),
                'debug_mode' => env('APP_DEBUG', false),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];

        return $basicCheck;
    }

    /**
     * Obter versão do Cycle ORM
     */
    private static function getCycleVersion(): string
    {
        try {
            $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

            foreach ($composerLock['packages'] as $package) {
                if ($package['name'] === 'cycle/orm') {
                    return $package['version'];
                }
            }

            return 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    /**
     * Obter extensões PHP relevantes
     */
    private static function getRelevantExtensions(): array
    {
        $relevant = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'json', 'mbstring', 'openssl'];
        $loaded = [];

        foreach ($relevant as $ext) {
            $loaded[$ext] = extension_loaded($ext);
        }

        return $loaded;
    }

    /**
     * Obter configuração relevante
     */
    private static function getConfiguration(Application $app): array
    {
        $config = [];

        try {
            if (function_exists('config')) {
                $config = [
                    'schema_cache' => config('cycle.schema.cache', false),
                    'auto_sync' => config('cycle.schema.auto_sync', false),
                    'default_connection' => config('cycle.database.default', 'unknown'),
                    'log_queries' => config('cycle.development.log_queries', false),
                ];
            }
        } catch (\Exception $e) {
            $config['error'] = 'Unable to load configuration';
        }

        return $config;
    }
}

// ============================================================================
// Helper para base_path se não existir
// ============================================================================

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string {
        $basePath = dirname(__DIR__, 4);
        return $basePath . ($path ? '/' . ltrim($path, '/') : '');
    }
}