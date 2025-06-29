# Installation

## Requirements

- PHP 8.1 or higher
- Express-PHP 2.1 or higher
- PDO extension
- JSON extension

## Composer Installation

```bash
composer require cafernandes/express-php-cycle-orm-extension
```

## Manual Installation

1. Download the package
2. Add to your `composer.json`:

```json
{
    "require": {
        "cafernandes/express-php-cycle-orm-extension": "^1.0"
    }
}
```

3. Run `composer install`

## Environment Setup

Copy the example environment file:

```bash
cp vendor/cafernandes/express-php-cycle-orm-extension/.env.example .env
```

Configure your database settings in `.env`.

## Verification

To verify the installation worked:

```php
<?php
require 'vendor/autoload.php';

use Express\Core\Application;

$app = new Application();

// Check if Cycle ORM is available
if ($app->has('cycle.orm')) {
    echo "✅ Cycle ORM Extension installed successfully!";
} else {
    echo "❌ Installation failed";
}
```

## Next Steps

- [Configuration](configuration.md)
- [Basic Usage](usage.md)
- [Advanced Features](advanced.md)