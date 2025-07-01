<?php

namespace Cycle\ORM;

class EntityManager
{
    public function hasChanges(): bool
    {
        return false;
    }

    public function beginTransaction(): void
    {
    }

    public function commit(): void
    {
    }

    public function rollback(): void
    {
    }
}
