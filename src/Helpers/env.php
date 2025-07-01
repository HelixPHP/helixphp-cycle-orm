<?php

namespace CAFernandes\ExpressPHP\CycleORM\Helpers;

/**
 * Helper para variáveis de ambiente
 *
 * @param string $key
 * @param string|false|null $default
 * @return string|false|null
 */
function env(string $key, string|false|null $default = null): string|false|null
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false) {
        return $default;
    }
    return (string)$value;
}
