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
        $context = ['entity' => $entityClass, 'identifier' => $identifier];
        parent::__construct($message, $context, $previous);
    }
}
