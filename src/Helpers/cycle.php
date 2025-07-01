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
    if ($request instanceof CycleRequest) {
        return $request;
    }
    throw new \RuntimeException(
        'Cycle ORM extension not loaded. Adicione o CycleMiddleware à sua aplicação.'
    );
}
