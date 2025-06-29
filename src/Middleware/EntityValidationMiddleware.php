<?php
namespace ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;

/**
 * Middleware para validação de entidades
 */
class EntityValidationMiddleware
{
    /**
     * Handle the middleware request
     */
    public function handle(Request $req, Response $res, callable $next): void
    {
        // Adicionar helper de validação
        $req->validateEntity = function (object $entity) {
            return app('cycle.validator')->validate($entity);
        };

        $next();
    }
}
