<?php

require_once 'vendor/autoload.php';

use Helix\Core\Application;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;

/**
 * Exemplos de queries avançadas com Cycle ORM
 */

$app = new Application();

// Query com múltiplos JOINs
$app->get('/api/advanced/users-with-stats', function($req, $res) {
    $repository = $req->repository(User::class);

    $users = $repository
        ->select()
        ->load('posts.comments') // Nested loading
        ->where('active', true)
        ->orderBy('createdAt', 'DESC')
        ->limit(10)
        ->fetchAll();

    // Calcular estatísticas
    $usersWithStats = array_map(function($user) {
        $postsCount = count($user->posts);
        $commentsCount = array_sum(array_map(fn($post) => count($post->comments), $user->posts));

        return [
            'user' => $user,
            'stats' => [
                'posts_count' => $postsCount,
                'comments_count' => $commentsCount,
                'avg_comments_per_post' => $postsCount > 0 ? round($commentsCount / $postsCount, 2) : 0
            ]
        ];
    }, $users);

    $res->json([
        'success' => true,
        'data' => $usersWithStats
    ]);
});

// Query com agregações personalizadas
$app->get('/api/advanced/post-analytics', function($req, $res) {
    // Usar query builder nativo para agregações complexas
    $db = $req->db->database();

    $analytics = $db->select()
        ->from('posts')
        ->columns([
            'author_id',
            'COUNT(*) as posts_count',
            'AVG(views) as avg_views',
            'MAX(created_at) as last_post_date'
        ])
        ->where('created_at', '>=', new \DateTime('-30 days'))
        ->groupBy('author_id')
        ->having('posts_count', '>', 5)
        ->orderBy('posts_count', 'DESC')
        ->fetchAll();

    $res->json([
        'success' => true,
        'data' => $analytics,
        'period' => 'Last 30 days'
    ]);
});

// Query com subqueries
$app->get('/api/advanced/popular-users', function($req, $res) {
    $orm = $req->orm;

    // Subquery para usuários com mais de X posts
    $minPosts = (int) ($req->query['min_posts'] ?? 3);

    $users = $orm->getRepository(User::class)
        ->select()
        ->where('id', 'IN', function($select) use ($minPosts) {
            return $select
                ->from('posts')
                ->columns('author_id')
                ->groupBy('author_id')
                ->having('COUNT(*)', '>', $minPosts);
        })
        ->load('posts')
        ->fetchAll();

    $res->json([
        'success' => true,
        'data' => $users,
        'criteria' => "Users with more than {$minPosts} posts"
    ]);
});

// Transação manual complexa
$app->post('/api/advanced/bulk-operation', function($req, $res) {
    $em = $req->em;

    try {
        // Operação em lote
        $operations = $req->body['operations'] ?? [];
        $results = [];

        foreach ($operations as $operation) {
            switch ($operation['type']) {
                case 'create_user':
                    $user = $req->entity(User::class, $operation['data']);
                    $em->persist($user);
                    $results[] = ['type' => 'user_created', 'id' => $user->getId()];
                    break;

                case 'create_post':
                    $post = $req->entity(Post::class, $operation['data']);
                    $em->persist($post);
                    $results[] = ['type' => 'post_created', 'id' => $post->getId()];
                    break;

                case 'update_user':
                    $user = $req->find(User::class, $operation['id']);
                    if ($user) {
                        foreach ($operation['data'] as $key => $value) {
                            if (property_exists($user, $key)) {
                                $user->$key = $value;
                            }
                        }
                        $em->persist($user);
                        $results[] = ['type' => 'user_updated', 'id' => $user->getId()];
                    }
                    break;
            }
        }

        // Commit será feito automaticamente pelo TransactionMiddleware

        $res->json([
            'success' => true,
            'results' => $results,
            'operations_count' => count($operations)
        ]);

    } catch (\Exception $e) {
        // Rollback automático pelo TransactionMiddleware
        $res->status(500)->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

$app->run();
