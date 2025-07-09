#!/bin/bash
# Cross-platform test coverage script for Unix/Linux/macOS

export XDEBUG_MODE=coverage
vendor/bin/phpunit --coverage-html coverage --coverage-clover coverage.xml