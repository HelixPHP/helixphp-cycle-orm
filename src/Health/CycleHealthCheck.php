<?php

namespace CAFernandes\ExpressPHP\CycleORM\Health;

use Psr\Container\ContainerInterface;

/**
 * Sistema de Health Check para Cycle ORM.
 */
class CycleHealthCheck
{
    /**
     * Verificar saúde geral do sistema Cycle ORM.
     *
     * @param object $app Container da aplicação
     *
     * @return array<string, mixed>
     */
    public static function check(object $app): array
    {
        $startTime = microtime(true);
        $status = [
            'cycle_orm' => 'healthy',
            'timestamp' => date('c'),
            'checks' => [],
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
                if ('healthy' !== $check['status']) {
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
     * Health check detalhado para debugging.
     *
     * @return array<string, mixed>
     */
    public static function detailedCheck(object $app): array
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
                'app_env' => function_exists('env') ? env('APP_ENV', 'unknown') : (getenv('APP_ENV') ?: 'unknown'),
                'debug_mode' => function_exists('env') ? env('APP_DEBUG', false) : (getenv('APP_DEBUG') ?: false),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
        ];

        return $basicCheck;
    }

    /**
     * Verificar serviços registrados.
     *
     * @param ContainerInterface|object $app
     *
     * @return array<string, mixed>
     */
    private static function checkServices(object $app): array
    {
        $services = [
            'cycle.database' => 'Database Manager',
            'cycle.orm' => 'ORM',
            'cycle.em' => 'Entity Manager',
            'cycle.schema' => 'Schema',
            'cycle.migrator' => 'Migrator',
            'cycle.repository' => 'Repository Factory',
        ];

        $registered = [];
        $missing = [];

        foreach ($services as $service => $name) {
            $hasService = false;

            if ($app instanceof ContainerInterface) {
                $hasService = $app->has($service);
            } elseif (is_object($app) && method_exists($app, 'has')) {
                $hasService = $app->has($service);
            } elseif (method_exists($app, 'getContainer')) {
                // Para Application do Express PHP
                $container = $app->getContainer();
                if (is_object($container) && method_exists($container, 'has')) {
                    $hasService = $container->has($service);
                }
            }

            if ($hasService) {
                $registered[] = $name;
            } else {
                $missing[] = $name;
            }
        }

        return [
            'registered' => $registered,
            'missing' => $missing,
            'status' => empty($missing) ? 'healthy' : 'unhealthy',
        ];
    }

    /**
     * Verificar conexão com database.
     *
     * @param ContainerInterface|object $app
     *
     * @return array<string, mixed>
     */
    private static function checkDatabase(object $app): array
    {
        $hasDatabase = false;
        $dbManager = null;
        $error = null;

        if ($app instanceof ContainerInterface && $app->has('cycle.database')) {
            $dbManager = $app->get('cycle.database');
        } elseif (method_exists($app, 'has') && $app->has('cycle.database')) {
            $dbManager = method_exists($app, 'make')
                ? $app->make('cycle.database')
                : null;
        } elseif (method_exists($app, 'getContainer')) {
            $container = $app->getContainer();
            if (
                is_object($container)
                && method_exists($container, 'has')
                && $container->has('cycle.database')
            ) {
                $dbManager = (
                    is_object($container)
                    && method_exists($container, 'get')
                ) ? $container->get('cycle.database') : null;
            }
        }

        if ($dbManager && is_object($dbManager) && method_exists($dbManager, 'database')) {
            try {
                $db = $dbManager->database();
                if (is_object($db) && method_exists($db, 'execute')) {
                    // Tenta executar um SELECT 1
                    $result = $db->execute('SELECT 1');
                    $hasDatabase = (false !== $result);
                } elseif (is_object($db) && method_exists($db, 'getPDO')) {
                    // Fallback: tenta usar PDO diretamente
                    $pdo = $db->getPDO();
                    $stmt = $pdo->query('SELECT 1');
                    $hasDatabase = (false !== $stmt);
                } else {
                    $hasDatabase = false;
                    $error = 'Método execute/getPDO não disponível na conexão.';
                }
            } catch (\Throwable $e) {
                $hasDatabase = false;
                $error = $e->getMessage();
            }
        }

        $status = [
            'status' => $hasDatabase ? 'healthy' : 'unhealthy',
        ];
        if ($error) {
            $status['error'] = $error;
        }

        return $status;
    }

    /**
     * Verificar schema.
     *
     * @param ContainerInterface|object $app
     *
     * @return array<string, mixed>
     */
    private static function checkSchema(object $app): array
    {
        $hasSchema = false;
        $orm = null;

        if ($app instanceof ContainerInterface && $app->has('cycle.orm')) {
            $orm = $app->get('cycle.orm');
        } elseif (method_exists($app, 'has') && $app->has('cycle.orm')) {
            $orm = method_exists($app, 'make')
                ? $app->make('cycle.orm')
                : null;
        } elseif (method_exists($app, 'getContainer')) {
            $container = $app->getContainer();
            if (
                is_object($container)
                && method_exists($container, 'has')
                && $container->has('cycle.orm')
            ) {
                $orm = (
                    is_object($container)
                    && method_exists($container, 'get')
                ) ? $container->get('cycle.orm') : null;
            }
        }

        if ($orm && is_object($orm) && method_exists($orm, 'getSchema')) {
            $schema = $orm->getSchema();
            $hasSchema = $schema ? true : false;
        }

        return [
            'status' => $hasSchema ? 'healthy' : 'unhealthy',
        ];
    }

    /**
     * Verificar performance.
     *
     * @return array<string, mixed>
     */
    private static function checkPerformance(object $app, float $startTime): array
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
     * Retorna a versão do Cycle ORM.
     */
    private static function getCycleVersion(): string
    {
        $composer = @file_get_contents(__DIR__ . '/../../composer.lock');
        if (!is_string($composer)) {
            return '';
        }
        $json = json_decode($composer, true);
        if (!is_array($json) || !isset($json['packages'])) {
            return '';
        }
        foreach ($json['packages'] as $package) {
            if (isset($package['name']) && str_starts_with($package['name'], 'cycle/')) {
                return (string) ($package['version'] ?? '');
            }
        }

        return '';
    }

    /**
     * Retorna extensões relevantes.
     *
     * @return array<int, string>
     */
    private static function getRelevantExtensions(): array
    {
        $exts = [
            'pdo_mysql' => \extension_loaded('pdo_mysql'),
            'pdo_pgsql' => \extension_loaded('pdo_pgsql'),
            'pdo_sqlite' => \extension_loaded('pdo_sqlite'),
        ];
        $result = [];
        foreach ($exts as $ext => $loaded) {
            if ($loaded) {
                $result[] = $ext;
            }
        }

        return $result;
    }

    /**
     * Retorna configuração do Cycle ORM.
     *
     * @return array<string, mixed>
     */
    private static function getConfiguration(object $app): array
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
    function base_path(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 4);

        return $basePath . ($path ? '/' . ltrim($path, '/') : '');
    }
}
