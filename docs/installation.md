# Installation

> **Dica:** Para um passo a passo completo, consulte também o [Guia Técnico e Quick Start](./guia-tecnico-quickstart.md).

## Requirements

- PHP 8.1 or higher
- Express-PHP 2.1 or higher
- PDO extension
- JSON extension

## Composer Installation

```bash
composer require express-php/cycle-orm-extension
```

## Environment Setup

Crie ou edite o arquivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=express_api
DB_USERNAME=root
DB_PASSWORD=

CYCLE_SCHEMA_CACHE=true
CYCLE_AUTO_SYNC=false
CYCLE_SCHEMA_STRICT=false
CYCLE_LOG_QUERIES=false
```

## Verification

Para verificar se a instalação funcionou:

```php
<?php
require 'vendor/autoload.php';

use Express\Core\Application;

$app = new Application();

if ($app->has('cycle.orm')) {
    echo "✅ Cycle ORM Extension installed successfully!";
} else {
    echo "❌ Installation failed";
}
```

## Next Steps

- [Configuração](configuration.md)
- [Uso Básico](usage.md)
- [Avançado](advanced.md)
