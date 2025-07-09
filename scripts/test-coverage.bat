@echo off
REM Cross-platform test coverage script for Windows

set XDEBUG_MODE=coverage
vendor\bin\phpunit --coverage-html coverage --coverage-clover coverage.xml