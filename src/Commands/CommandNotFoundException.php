<?php

namespace Helix\CycleORM\Commands;

class CommandNotFoundException extends \RuntimeException
{
    public function __construct(string $commandName)
    {
        parent::__construct("Command '{$commandName}' not found.");
    }
}
