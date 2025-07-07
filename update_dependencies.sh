#!/bin/bash

echo "=== Updating PivotPHP Cycle ORM Dependencies ==="
echo ""

# Change to the script's directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Working directory: $(pwd)"
echo ""

# Check if composer.json exists
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found!"
    exit 1
fi

echo "📋 Current composer.json requires:"
grep -A1 '"pivotphp/core"' composer.json
echo ""

# Remove composer.lock if it exists
if [ -f "composer.lock" ]; then
    echo "🗑️  Removing composer.lock..."
    rm -f composer.lock
fi

# Update dependencies
echo "📦 Updating dependencies from Packagist..."
/usr/local/bin/composer update --no-interaction

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Dependencies updated successfully!"
    echo ""
    echo "🎯 Next steps:"
    echo "  1. Run tests: composer test"
    echo "  2. Check PSR-12: composer cs:check"
    echo "  3. Run PHPStan: composer phpstan"
else
    echo ""
    echo "❌ Error updating dependencies!"
    echo ""
    echo "💡 Troubleshooting:"
    echo "  • Check if pivotphp/core is published on Packagist"
    echo "  • Verify network connectivity"
    echo "  • Try: composer update -vvv for verbose output"
fi
