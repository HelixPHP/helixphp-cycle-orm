<?php

namespace CAFernandes\ExpressPHP\CycleORM\Helpers;

/**
 * Helper para variáveis de ambiente.
 */
function env(string $key, null|false|string $default = null): null|false|string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if (false === $value) {
        return $default;
    }

    return (string) $value;
}
