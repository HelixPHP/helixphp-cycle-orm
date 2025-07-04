<?php

namespace Cycle\ORM\Select;

class Select
{
    public function where(mixed $field, mixed $value): self
    {
        // Simula encadeamento
        return $this;
    }
}
