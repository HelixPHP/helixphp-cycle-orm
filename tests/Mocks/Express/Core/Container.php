<?php

namespace Express\Core;

class Container
{
    public function singleton(mixed ...$args): bool
    {
        return true;
    }

    public function booted(): bool
    {
        return true;
    }

    public function alias(mixed ...$args): bool
    {
        return true;
    }

    public function make(mixed ...$args): object
    {
        return new \stdClass();
    }
}
