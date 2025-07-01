<?php

namespace CAFernandes\ExpressPHP\CycleORM\Commands;

/**
 * Command base que funciona independente do sistema de console.
 */
abstract class BaseCommand
{
    /**
     * Argumentos do comando.
     *
     * @var array<string, mixed>
     */
    protected array $arguments = [];

    /**
     * Opções do comando.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $this->parseArguments($args);
    }

    abstract public function handle(): int;

    /**
     * Obtém o argumento pelo nome.
     */
    protected function argument(string $name): ?string
    {
        $value = $this->arguments[$name] ?? null;

        return is_string($value) ? $value : null;
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

    /**
     * Exibe uma tabela no console.
     *
     * @param array<int, string>             $headers
     * @param array<int, array<int, string>> $rows
     */
    protected function table(array $headers, array $rows): void
    {
        // Implementação simples de tabela
        echo implode("\t", $headers) . PHP_EOL;
        echo str_repeat('-', 50) . PHP_EOL;
        foreach ($rows as $row) {
            echo implode("\t", $row) . PHP_EOL;
        }
    }

    /**
     * @param array<string, mixed> $args
     */
    private function parseArguments(array $args): void
    {
        foreach ($args as $key => $value) {
            if (0 === strpos($key, '--')) {
                $this->options[substr($key, 2)] = $value;
            } else {
                $this->arguments[$key] = $value;
            }
        }
    }
}
