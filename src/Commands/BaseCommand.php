<?php
namespace CAFernandes\ExpressPHP\CycleORM\Commands;

/**
 * Command base que funciona independente do sistema de console
 */
abstract class BaseCommand
{
    protected array $arguments = [];
    protected array $options = [];

    public function __construct(array $args = [])
    {
        $this->parseArguments($args);
    }

    abstract public function handle(): int;

    protected function argument(string $name): ?string
    {
        return $this->arguments[$name] ?? null;
    }

    protected function option(string $name): bool
    {
        return isset($this->options[$name]);
    }

    protected function info(string $message): void
    {
        echo "\033[32m[INFO]\033[0m {$message}" . PHP_EOL;
    }

    protected function error(string $message): void
    {
        echo "\033[31m[ERROR]\033[0m {$message}" . PHP_EOL;
    }

    protected function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    protected function table(array $headers, array $rows): void
    {
        // Implementação simples de tabela
        echo implode("\t", $headers) . PHP_EOL;
        echo str_repeat("-", 50) . PHP_EOL;
        foreach ($rows as $row) {
            echo implode("\t", $row) . PHP_EOL;
        }
    }

    private function parseArguments(array $args): void
    {
        foreach ($args as $key => $value) {
            if (strpos($key, '--') === 0) {
                $this->options[substr($key, 2)] = $value;
            } else {
                $this->arguments[$key] = $value;
            }
        }
    }
}