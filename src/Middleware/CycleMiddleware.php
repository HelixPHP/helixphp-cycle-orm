<?php
namespace ExpressPHP\CycleORM\Middleware;

use Express\Http\Request;
use Express\Http\Response;

/**
 * Middleware para injeção automática do Cycle ORM
 */
class CycleMiddleware
{
    /**
     * Handle the middleware request
     */
    public function handle(Request $req, Response $res, callable $next): void
    {
        // Injetar ORM services no request para fácil acesso
        $req->orm = app('cycle.orm');
        $req->em = app('cycle.em');
        $req->db = app('cycle.database');

        // Helper para repositories
        $req->repository = function (string $entityClass) use ($req) {
            return $req->orm->getRepository($entityClass);
        };

        // Helper para criação de entidades
        $req->entity = function (string $entityClass, array $data = []) {
            return new $entityClass(...array_values($data));
        };

        $next();
    }
}