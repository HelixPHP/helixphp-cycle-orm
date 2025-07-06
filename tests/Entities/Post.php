<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(name: 'posts')]
class Post
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string')]
    public string $title;

    #[Column(type: 'text')]
    public string $content;

    #[Column(type: 'integer')]
    public int $userId;

    #[BelongsTo(target: User::class)]
    public ?User $user = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $createdAt = null;

    public function __construct(string $title, string $content, int $userId)
    {
        $this->title = $title;
        $this->content = $content;
        $this->userId = $userId;
        $this->createdAt = new \DateTimeImmutable();
    }
}
