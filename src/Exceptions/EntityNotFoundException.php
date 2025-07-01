<?php

namespace CAFernandes\ExpressPHP\CycleORM\Exceptions;

class EntityNotFoundException extends CycleORMException
{
    private string $entityClass;

    private int|string $identifier;

    public function __construct(
        string $entityClass,
        int|string $identifier,
        ?\Throwable $previous = null
    ) {
        $this->entityClass = $entityClass;
        $this->identifier = $identifier;
        $message = "Entity {$entityClass} with identifier {$identifier} not found";
        parent::__construct($message, 0, $previous);
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getIdentifier(): int|string
    {
        return $this->identifier;
    }
}
