<?php

namespace CAFernandes\ExpressPHP\CycleORM\Exceptions;

class EntityNotFoundException extends CycleORMException
{
    public function __construct(
        string $entityClass,
        int|string $identifier,
        ?\Throwable $previous = null
    ) {
        $message = "Entity {$entityClass} with identifier {$identifier} not found";
        parent::__construct($message, 0, $previous);
    }
}
