<?php
namespace Express\Core;

class Container {
    public function singleton(...$args) { return true; }
    public function booted() { return true; }
    public function alias(...$args) { return true; }
    public function make(...$args) { return new \stdClass(); }
}
