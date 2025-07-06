<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table('users')]
class User
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string')]
    public string $name = '';

    #[Column(type: 'string')]
    public string $email = '';

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $createdAt = null;

    public function __construct(string $name = '', string $email = '')
    {
        $this->name = $name;
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
    }
}
