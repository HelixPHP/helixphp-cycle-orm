<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Fixtures;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'test_entities')]
class TestEntity
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'boolean', default: true)]
    public bool $active = true;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}