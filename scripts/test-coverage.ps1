# Cross-platform test coverage script for PowerShell

$env:XDEBUG_MODE = "coverage"
& vendor/bin/phpunit --coverage-html coverage --coverage-clover coverage.xml