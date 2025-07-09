<?php
/**
 * Cross-platform test coverage runner
 * Works on Windows, macOS, and Linux
 */

// Set XDEBUG_MODE environment variable
putenv('XDEBUG_MODE=coverage');
$_ENV['XDEBUG_MODE'] = 'coverage';

// Determine the correct phpunit executable
$isWindows = PHP_OS_FAMILY === 'Windows';
$phpunit = $isWindows ? 'vendor\\bin\\phpunit.bat' : 'vendor/bin/phpunit';

// Build the command
$command = sprintf(
    '%s --coverage-html coverage --coverage-clover coverage.xml',
    $phpunit
);

echo "Running coverage tests...\n";
echo "Command: {$command}\n\n";

// Execute the command and capture the exit code
passthru($command, $exitCode);

// Exit with the same code as phpunit
exit($exitCode);