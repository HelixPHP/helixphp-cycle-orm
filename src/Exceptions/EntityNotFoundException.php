<?php
namespace CAFernandes\ExpressPHP\CycleORM\Exceptions;

class EntityNotFoundException extends CycleORMException
{
    public function __construct(string $entityClass, $identifier, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Entity {$entityClass} with identifier {$identifier} not found",
            ['entity' => $entityClass, 'identifier' => $identifier],
            $previous
        );
    }
}