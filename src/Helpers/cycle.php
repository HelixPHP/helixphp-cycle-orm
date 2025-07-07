<?php

use PivotPHP\CycleORM\Http\CycleRequest;
use PivotPHP\Core\Http\Request;

/**
 * Helper para garantir que temos um CycleRequest.
 *
 * @throws RuntimeException Se o request não for um CycleRequest
 */
function cycle(Request $request): CycleRequest
{
    // Não faz instanceof, sempre cria o wrapper
    return new CycleRequest($request);
}
