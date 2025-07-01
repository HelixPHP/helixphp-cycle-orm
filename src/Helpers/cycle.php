<?php

use Express\Http\Request;
use CAFernandes\ExpressPHP\CycleORM\Http\CycleRequest;

/**
 * Helper para garantir que temos um CycleRequest
 *
 * @param Request $request
 * @return CycleRequest
 * @throws \RuntimeException Se o request não for um CycleRequest
 */
function cycle(Request $request): CycleRequest
{
    // Não faz instanceof, sempre cria o wrapper
    return new CycleRequest($request);
}
