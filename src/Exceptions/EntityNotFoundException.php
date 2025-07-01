<?php

namespace CAFernandes\ExpressPHP\CycleORM\Exceptions;

class EntityNotFoundException extends CycleORMException
{
    /**
     * @param string $entityClass
     * @param string|int $identifier
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $entityClass,
        string|int $identifier,
        ?\Throwable $previous = null
    ) {
        $message = "Entity {$entityClass} with identifier {$identifier} not found";
        parent::__construct($message, 0, $previous);
    }
}
