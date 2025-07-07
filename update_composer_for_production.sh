#!/bin/bash

echo "=== Updating composer.json for Production (Packagist) ==="
echo ""

cd /home/cfernandes/pivotphp/pivotphp-cycle-orm || exit 1

# Backup current composer.json
cp composer.json composer.json.local

# Update composer.json to use Packagist version
cat > composer.json << 'EOF'
{
  "name": "pivotphp/cycle-orm",
  "description": "Robust and well-tested Cycle ORM integration for PivotPHP microframework with type safety and comprehensive testing",
  "keywords": [
    "pivotphp",
    "cycle-orm",
    "database",
    "orm",
    "microframework",
    "type-safe",
    "phpstan",
    "repository-pattern",
    "monitoring",
    "middleware"
  ],
  "type": "library",
  "license": "MIT",
  "authors": [
    {
      "name": "Caio Alberto Fernandes",
      "homepage": "https://github.com/CAFernandes"
    }
  ],
  "require": {
    "php": "^8.1",
    "pivotphp/core": "^1.0",
    "cycle/orm": "^2.10",
    "cycle/annotated": "^4.3",
    "cycle/migrations": "^4.2.5",
    "cycle/schema-builder": "^2.0",
    "spiral/tokenizer": "^3.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0",
    "phpstan/phpstan": "^1.0",
    "squizlabs/php_codesniffer": "^3.13",
    "friendsofphp/php-cs-fixer": "^3.76"
  },
  "autoload": {
    "psr-4": {
      "PivotPHP\\CycleORM\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "PivotPHP\\CycleORM\\Tests\\": "tests/",
      "Cycle\\ORM\\Select\\": "tests/Mocks/Cycle/ORM/Select/"
    }
  },
  "scripts": {
    "test": "phpunit",
    "test:unit": "phpunit --testsuite=Unit",
    "test:feature": "phpunit --testsuite=Feature",
    "test:integration": "phpunit --testsuite=Integration",
    "test:database": "phpunit --testsuite=Database",
    "test-coverage": "phpunit --coverage-html coverage",
    "phpstan": "phpstan analyse src --level=9",
    "cs:check": "phpcs --standard=phpcs.xml --report=full",
    "cs:check:summary": "phpcs --standard=phpcs.xml --report=summary",
    "cs:check:diff": "phpcs --standard=phpcs.xml --report=diff",
    "cs:fix": "phpcbf --standard=phpcs.xml",
    "cs:fix:dry": "phpcbf --standard=phpcs.xml --dry-run",
    "psr12:validate": [
      "@cs:check:summary",
      "echo 'PSR-12 validation completed!'"
    ],
    "psr12:fix": [
      "@cs:fix",
      "@cs:check:summary",
      "echo 'PSR-12 auto-fix completed!'"
    ],
    "quality:psr12": [
      "@psr12:validate",
      "@phpstan",
      "echo 'Quality check with PSR-12 completed!'"
    ]
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
EOF

echo "✅ composer.json updated to use Packagist dependencies"
echo ""
echo "📊 Changes made:"
echo "  • Removed local path repository"
echo "  • Changed pivotphp/core from @dev to ^1.0"
echo "  • Changed minimum-stability from dev to stable"
echo "  • Backup saved as composer.json.local"
echo ""
echo "🔄 Next steps:"
echo "  1. Remove composer.lock: rm -f composer.lock"
echo "  2. Update dependencies: composer update"
echo "  3. Run tests: composer test"
echo ""
echo "💡 To restore local development setup:"
echo "  cp composer.json.local composer.json"
