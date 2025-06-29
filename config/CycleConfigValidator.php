<?php


class CycleConfigValidator
{
    public static function validate(array $config): array
    {
        $errors = [];

        // Validar configuração de database
        if (!isset($config['database']['default'])) {
            $errors[] = 'Missing database.default configuration';
        }

        $default = $config['database']['default'];
        if (!isset($config['database']['connections'][$default])) {
            $errors[] = "Default connection '{$default}' not configured";
        }

        // Validar configuração de entidades
        if (!isset($config['entities']['directories']) || empty($config['entities']['directories'])) {
            $errors[] = 'At least one entity directory must be configured';
        }

        // Verificar se diretórios existem
        foreach ($config['entities']['directories'] as $dir) {
            if (!is_dir($dir)) {
                $errors[] = "Entity directory does not exist: {$dir}";
            }
        }

        // Validar migrations directory
        $migrationDir = $config['migrations']['directory'];
        if (!is_dir($migrationDir) && !is_writable(dirname($migrationDir))) {
            $errors[] = "Migration directory is not writable: {$migrationDir}";
        }

        return $errors;
    }
}