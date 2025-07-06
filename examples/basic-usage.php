<?php
require_once 'vendor/autoload.php';

use Helix\Core\Application;
use App\Models\User;
use App\Models\Post;

/**
 * Exemplo básico de uso da extensão Express-PHP Cycle ORM
 * Demonstra CRUD completo com relacionamentos
 */

// Inicializar aplicação Express-PHP
$app = new Application();

// A extensão Cycle ORM é carregada automaticamente via auto-discovery!
// Não é necessário registrar manualmente

// ============================================================================
// CRUD BÁSICO - USUÁRIOS
// ============================================================================

// Listar todos os usuários
$app->get('/api/users', function($req, $res) {
    // Repository injetado automaticamente pelo CycleMiddleware
    $users = $req->repository(User::class)->findAll();

    $res->json([
        'success' => true,
        'data' => $users,
        'count' => count($users)
    ]);
});

// Buscar usuário por ID
$app->get('/api/users/:id', function($req, $res) {
    $id = (int) $req->params['id'];

    // Helper find injetado automaticamente
    $user = $req->find(User::class, $id);

    if (!$user) {
        return $res->status(404)->json([
            'success' => false,
            'error' => 'User not found'
        ]);
    }

    $res->json([
        'success' => true,
        'data' => $user
    ]);
});

// Criar novo usuário
$app->post('/api/users', function($req, $res) {
    try {
        // Helper entity para criar entidade com dados do request
        $user = $req->entity(User::class, $req->body);

        // Validar entidade
        $validation = $req->validateEntity($user);
        if (!$validation['valid']) {
            return $res->status(400)->json([
                'success' => false,
                'errors' => $validation['errors']
            ]);
        }

        // EntityManager injetado automaticamente
        $req->em->persist($user);
        // Commit automático via TransactionMiddleware

        $res->status(201)->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully'
        ]);

    } catch (\Exception $e) {
        $res->status(500)->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Atualizar usuário
$app->put('/api/users/:id', function($req, $res) {
    try {
        $id = (int) $req->params['id'];
        $user = $req->find(User::class, $id);

        if (!$user) {
            return $res->status(404)->json([
                'success' => false,
                'error' => 'User not found'
            ]);
        }

        // Atualizar propriedades
        foreach ($req->body as $property => $value) {
            if (property_exists($user, $property)) {
                $user->$property = $value;
            }
        }

        $user->setUpdatedAt(new \DateTime());

        $req->em->persist($user);

        $res->json([
            'success' => true,
            'data' => $user,
            'message' => 'User updated successfully'
        ]);

    } catch (\Exception $e) {
        $res->status(500)->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Deletar usuário
$app->delete('/api/users/:id', function($req, $res) {
    try {
        $id = (int) $req->params['id'];
        $user = $req->find(User::class, $id);

        if (!$user) {
            return $res->status(404)->json([
                'success' => false,
                'error' => 'User not found'
            ]);
        }

        $req->em->delete($user);

        $res->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);

    } catch (\Exception $e) {
        $res->status(500)->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// ============================================================================
// RECURSOS AVANÇADOS
// ============================================================================

// Paginação e filtros
$app->get('/api/users/search', function($req, $res) {
    $repository = $req->repository(User::class);
    $query = $repository->select();

    // Aplicar filtros usando helper
    $filters = $req->query['filters'] ?? [];
    if (!empty($filters)) {
        $query = \Helix\CycleORM\Helpers\CycleHelpers::applyFilters(
            $query,
            $filters,
            ['name', 'email', 'status'] // campos permitidos
        );
    }

    // Aplicar busca
    $search = $req->query['search'] ?? null;
    if ($search) {
        $query = \Helix\CycleORM\Helpers\CycleHelpers::applySearch(
            $query,
            $search,
            ['name', 'email'] // campos de busca
        );
    }

    // Aplicar ordenação
    $sortBy = $req->query['sort_by'] ?? 'createdAt';
    $sortDirection = $req->query['sort_direction'] ?? 'desc';
    $query = \Helix\CycleORM\Helpers\CycleHelpers::applySorting(
        $query,
        $sortBy,
        $sortDirection,
        ['name', 'email', 'createdAt'] // campos permitidos
    );

    // Paginação usando helper injetado
    $page = (int) ($req->query['page'] ?? 1);
    $perPage = (int) ($req->query['per_page'] ?? 15);
    $result = $req->paginate($query, $page, $perPage);

    $res->json([
        'success' => true,
        'data' => $result['data'],
        'pagination' => $result['pagination']
    ]);
});

// Relacionamentos complexos
$app->get('/api/users/:id/posts', function($req, $res) {
    $id = (int) $req->params['id'];

    // Carregar usuário com posts usando eager loading
    $user = $req->repository(User::class)
        ->select()
        ->load('posts', [
            'method' => \Cycle\ORM\Select::SINGLE_QUERY
        ])
        ->where('id', $id)
        ->fetchOne();

    if (!$user) {
        return $res->status(404)->json([
            'success' => false,
            'error' => 'User not found'
        ]);
    }

    $res->json([
        'success' => true,
        'data' => [
            'user' => $user,
            'posts' => $user->posts,
            'posts_count' => count($user->posts)
        ]
    ]);
});

// Health check endpoint
$app->get('/health/cycle', function($req, $res) {
    $health = \Helix\CycleORM\Health\CycleHealthCheck::check($req->app ?? $app);

    $statusCode = $health['cycle_orm'] === 'healthy' ? 200 : 503;

    $res->status($statusCode)->json($health);
});

// Executar aplicação
$app->run();
