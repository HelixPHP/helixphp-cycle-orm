<?php

declare(strict_types=1);

namespace CAFernandes\ExpressPHP\CycleORM\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception class for Cycle ORM Extension
 */
class CycleORMException extends Exception
{
    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }
}
