<?php

namespace CAFernandes\ExpressPHP\CycleORM\Commands;

class CommandRegistry
{
    /**
     * @var array<string, class-string<BaseCommand>>
     */
    private array $commands = [];

    /**
     * @param array<string, mixed> $args
     */
    public function run(string $commandName, array $args = []): int
    {
        if (!$this->hasCommand($commandName)) {
            throw new CommandNotFoundException($commandName);
        }

        $commandClass = $this->commands[$commandName];
        $command = new $commandClass($args);

        return $command->handle();
    }

    /**
     * @return array<int, string>
     */
    public function getRegisteredCommands(): array
    {
        return array_keys($this->commands);
    }

    public function register(string $name, string $commandClass): void
    {
        if (!class_exists($commandClass)) {
            throw new \InvalidArgumentException("Command class {$commandClass} does not exist");
        }

        if (!is_subclass_of($commandClass, BaseCommand::class)) {
            throw new \InvalidArgumentException("Command class {$commandClass} must extend BaseCommand");
        }

        $this->commands[$name] = $commandClass;
    }

    public function hasCommand(string $name): bool
    {
        return isset($this->commands[$name]);
    }
}
