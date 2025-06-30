<?php

namespace CAFernandes\ExpressPHP\CycleORM\Exceptions;

/**
 * NOVO: Exceções específicas para melhor debugging
 */
class CycleORMException extends \Exception
{
  protected array $context = [];

  public function __construct(
    string $message,
    array $context = [],
    ?\Throwable $previous = null
  ) {
    parent::__construct($message, 0, $previous);
    $this->context = $context;
  }

  public function getContext(): array
  {
    return $this->context;
  }
}
