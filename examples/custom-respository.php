<?php

namespace App\Repositories;

use Cycle\ORM\Select\Repository;
use App\Models\User;

/**
 * Repository customizado para User
 */
class UserRepository extends Repository
{
    /**
     * Encontrar usuários ativos
     */
    public function findActiveUsers(): array
    {
        return $this->select()
            ->where('active', true)
            ->orderBy('createdAt', 'DESC')
            ->fetchAll();
    }

    /**
     * Encontrar usuários por período
     */
    public function findByDateRange(\DateTime $start, \DateTime $end): array
    {
        return $this->select()
            ->where('createdAt', '>=', $start)
            ->where('createdAt', '<=', $end)
            ->orderBy('createdAt', 'ASC')
            ->fetchAll();
    }

    /**
     * Buscar usuários com posts populares
     */
    public function findUsersWithPopularPosts(int $minViews = 1000): array
    {
        return $this->select()
            ->distinct()
            ->load('posts', [
                'method' => \Cycle\ORM\Select::SINGLE_QUERY,
                'load' => function($q) use ($minViews) {
                    $q->where('views', '>=', $minViews);
                }
            ])
            ->fetchAll();
    }

    /**
     * Estatísticas de usuário
     */
    public function getUserStats(int $userId): ?array
    {
        $user = $this->findByPK($userId);
        if (!$user) {
            return null;
        }

        // Carregar relacionamentos
        $user = $this->select()
            ->where('id', $userId)
            ->load('posts.comments')
            ->fetchOne();

        $postsCount = count($user->posts);
        $commentsCount = 0;

        foreach ($user->posts as $post) {
            $commentsCount += count($post->comments);
        }

        return [
            'user_id' => $userId,
            'posts_count' => $postsCount,
            'comments_received' => $commentsCount,
            'avg_comments_per_post' => $postsCount > 0 ? $commentsCount / $postsCount : 0,
            'last_post_date' => $postsCount > 0 ? $user->posts[0]->createdAt : null
        ];
    }
}

// ============================================================================
// app/Models/User.php - Exemplo de Entidade Completa
// ============================================================================

namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\HasMany;

#[Entity(
    table: 'users',
    repository: 'App\Repositories\UserRepository'
)]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'string', length: 255, unique: true)]
    public string $email;

    #[Column(type: 'string', length: 255)]
    public string $password;

    #[Column(type: 'boolean', default: true)]
    public bool $active = true;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;

    #[HasMany(target: 'App\Models\Post', load: 'lazy')]
    public array $posts = [];

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters e Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getPosts(): array
    {
        return $this->posts;
    }

    public function addPost(Post $post): void
    {
        $this->posts[] = $post;
    }
}

// ============================================================================
// app/Models/Post.php - Entidade com Relacionamentos
// ============================================================================

namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\BelongsTo;
use Cycle\Annotated\Annotation\HasMany;

#[Entity(table: 'posts')]
class Post
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $title;

    #[Column(type: 'text')]
    public string $content;

    #[Column(type: 'integer', default: 0)]
    public int $views = 0;

    #[Column(type: 'boolean', default: true)]
    public bool $published = true;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;

    #[BelongsTo(target: 'App\Models\User', load: 'eager')]
    public User $author;

    #[HasMany(target: 'App\Models\Comment', load: 'lazy')]
    public array $comments = [];

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Métodos úteis
    public function incrementViews(): void
    {
        $this->views++;
        $this->setUpdatedAt(new \DateTime());
    }

    public function publish(): void
    {
        $this->published = true;
        $this->setUpdatedAt(new \DateTime());
    }

    public function unpublish(): void
    {
        $this->published = false;
        $this->setUpdatedAt(new \DateTime());
    }

    public function getCommentsCount(): int
    {
        return count($this->comments);
    }

    // Getters e Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
        $this->setUpdatedAt(new \DateTime());
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->setUpdatedAt(new \DateTime());
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): void
    {
        $this->author = $author;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getComments(): array
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): void
    {
        $this->comments[] = $comment;
    }
}