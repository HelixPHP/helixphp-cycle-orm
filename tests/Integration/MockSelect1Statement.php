<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests\Integration;

use Cycle\Database\StatementInterface;

/**
 * @implements \IteratorAggregate<int, array<string, string>>
 */
class MockSelect1Statement implements StatementInterface, \IteratorAggregate
{
    public const FETCH_ASSOC = 2;
    /** @param array<mixed> $params */
    public function execute(array $params = []): bool
    {
        fwrite(STDERR, "\nDEBUG execute SELECT 1\n");
        return true;
    }
    public function fetch(int $mode = \Cycle\Database\StatementInterface::FETCH_ASSOC): mixed
    {
        fwrite(STDERR, "\nDEBUG fetch SELECT 1\n");
        return ['1' => '1'];
    }
    /** @return array<mixed> */
    public function fetchAll(int $mode = StatementInterface::FETCH_ASSOC): array
    {
        fwrite(STDERR, "\nDEBUG fetchAll SELECT 1\n");
        return [['1' => '1']];
    }
    public function fetchColumn(?int $columnNumber = null): mixed
    {
        fwrite(STDERR, "\nMOCK SELECT 1 fetchColumn chamado (INTEGRATION)\n");
        return '1';
    }
    /** @param array<mixed> $args */
    public function fetchObject(string $className = 'stdClass', array $args = []): object|false
    {
        return (object)['1' => '1'];
    }
    public function getIterator(): \Traversable
    {
        // Corrige o tipo retornado para array<string, string>
        return new \ArrayIterator(
            [
                ['1' => '1']
            ]
        );
    }
    public function rowCount(): int
    {
        return 1;
    }
    public function columnCount(): int
    {
        return 1;
    }
    public function closeCursor(): bool
    {
        return true;
    }
    public function errorCode(): ?string
    {
        return null;
    }
    /** @return array<mixed> */
    public function errorInfo(): array
    {
        return [];
    }
    public function getQueryString(): string
    {
        return 'SELECT 1';
    }
    public function close(): void
    {
    }
}
